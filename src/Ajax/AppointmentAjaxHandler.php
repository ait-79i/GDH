<?php
namespace GDH\Ajax;

use GDH\PostTypes\AppointmentPostType;
use GDH\Services\EmailTemplateService;
use GDH\Services\Logger;

class AppointmentAjaxHandler
{
    private $logger;
    private $emailService;

    public function __construct(Logger $logger)
    {
        $this->logger       = $logger;
        $this->emailService = new EmailTemplateService($this->logger);
        $this->init();
    }

    private function init()
    {
        // For logged-in users
        add_action('wp_ajax_gdh_rdv_submit', [$this, 'handleSubmit']);

        // For non-logged-in users (public form)
        add_action('wp_ajax_nopriv_gdh_rdv_submit', [$this, 'handleSubmit']);
    }

    public function handleSubmit()
    {
        try {
            // Log received data for debugging
            $this->logger->info('GDH AJAX: Request received - ' . json_encode($_POST));

            // Verify nonce
            if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'gdh_rdv_nonce')) {
                $this->logger->error('GDH AJAX: Nonce verification failed');
                wp_send_json_error([
                    'message' => 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.',
                ], 403);
            }

            // Get form data
            if (! isset($_POST['formData'])) {
                $this->logger->error('GDH AJAX: No form data received');
                wp_send_json_error([
                    'message' => 'Aucune donnée reçue.',
                ], 400);
            }

            // Decode JSON string to array
            $formData = json_decode(stripslashes($_POST['formData']), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('GDH AJAX: JSON decode error - ' . json_last_error_msg());
                wp_send_json_error([
                    'message' => 'Erreur de format des données.',
                ], 400);
            }

            // Log form data structure
            $this->logger->info('GDH AJAX: Form data - ' . json_encode($formData));

            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'postal_code', 'city'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    $this->logger->error("GDH AJAX: Missing required field: {$field}");
                    wp_send_json_error([
                        'message' => "Tous les champs obligatoires doivent être remplis. Champ manquant: {$field}",
                    ], 400);
                }
            }

            // Validate email
            if (! is_email($formData['email'])) {
                $this->logger->error('GDH AJAX: Invalid email format');
                wp_send_json_error([
                    'message' => 'L\'adresse email n\'est pas valide.',
                ], 400);
            }

            // Validate slots
            if (empty($formData['slots']) || ! is_array($formData['slots'])) {
                $this->logger->error('GDH AJAX: No slots provided or invalid format');
                wp_send_json_error([
                    'message' => 'Veuillez sélectionner au moins un créneau de disponibilité.',
                ], 400);
            }

            // Validate terms acceptance only if CGV page is configured
            $cgvPageId = get_option('cgv_page_id', '');
            if (! empty($cgvPageId)) {
                // CGV is configured, so terms acceptance is required
                $termsAccepted = isset($formData['accept_terms']) &&
                    ($formData['accept_terms'] === true ||
                    $formData['accept_terms'] === 'true' ||
                    $formData['accept_terms'] === 1 ||
                    $formData['accept_terms'] === '1');

                if (! $termsAccepted) {
                    $this->logger->error('GDH AJAX: Terms not accepted - value: ' . var_export($formData['accept_terms'], true));
                    wp_send_json_error([
                        'message' => 'Vous devez accepter les conditions générales.',
                    ], 400);
                }
            }

            // Create appointment
            $post_id = AppointmentPostType::createAppointment($formData);

            if (! $post_id) {
                $this->logger->error('GDH AJAX: Failed to create appointment');
                wp_send_json_error([
                    'message' => 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.',
                ], 500);
            }

            $this->logger->info("GDH AJAX: Appointment created successfully - ID: {$post_id}");

            // Try to send email (non-blocking for booking result)
            try {
                $sent = $this->emailService->sendOnAppointment($post_id, $formData);
                if (! $sent) {
                    $this->logger->error('GDH AJAX: Email sending failed or skipped');
                }
            } catch (\Throwable $e) {
                $this->logger->error('GDH AJAX: Email exception - ' . $e->getMessage());
            }

            // Send success response
            wp_send_json_success([
                'message'        => 'Votre demande de rendez-vous a été enregistrée avec succès !',
                'appointment_id' => $post_id,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('GDH AJAX Exception: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            ], 500);
        }
    }
}
