<?php
namespace GDH\Ajax;

use GDH\PostTypes\AppointmentPostType;
use GDH\Services\EmailTemplateService;
use GDH\Services\Logger;
use GDH\Services\RecipientService;

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
        // Security: Add rate limiting and validation
        add_action('wp_ajax_gdh_rdv_submit', [$this, 'handleSubmit']);
        add_action('wp_ajax_nopriv_gdh_rdv_submit', [$this, 'handleSubmit']);
    }

    public function handleSubmit()
    {
        try {
            // Security: Verify nonce first
            if (!check_ajax_referer('gdh_rdv_nonce', 'nonce', false)) {
                $this->logger->error('GDH AJAX: Nonce verification failed');
                wp_send_json_error([
                    'message' => 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.',
                ], 403);
            }
            
            // Security: Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                wp_send_json_error(['message' => 'Méthode non autorisée'], 405);
            }
            
            // Log request received
            $this->logger->info('GDH AJAX: Request received');

            // Get form data
            if (! isset($_POST['formData'])) {
                $this->logger->error('GDH AJAX: No form data received');
                wp_send_json_error([
                    'message' => 'Aucune donnée reçue.',
                ], 400);
            }

            // Security: Sanitize and decode JSON
            $rawData = isset($_POST['formData']) ? wp_unslash($_POST['formData']) : '';
            if (strlen($rawData) > 10000) { // Limit payload size
                wp_send_json_error(['message' => 'Données trop volumineuses'], 413);
            }
            
            $formData = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('GDH AJAX: JSON decode error - ' . json_last_error_msg());
                wp_send_json_error([
                    'message' => 'Erreur de format des données.',
                ], 400);
            }

            // Log form data structure
            $this->logger->info('GDH AJAX: Form data - ' . json_encode($formData));

            // Security: Sanitize all form data
            $formData = $this->sanitizeFormData($formData);
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'postal_code', 'city'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    $this->logger->error("GDH AJAX: Missing required field: {$field}");
                    wp_send_json_error([
                        'message' => 'Tous les champs obligatoires doivent être remplis.',
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


            // Security: Get recipient info from server configuration, not form data
            $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
            if ($recipientInfo) {
                update_post_meta($post_id, '_gdh_destinataire_email', $recipientInfo['email']);
                update_post_meta($post_id, '_gdh_destinataire_name', $recipientInfo['name']);
                $this->logger->info("GDH AJAX: Destinataire saved - {$recipientInfo['name']} ({$recipientInfo['email']})");
            } else {
                $this->logger->error("GDH AJAX: Failed to resolve secure recipient info");
                wp_send_json_error([
                    'message' => 'Erreur de configuration du destinataire.',
                ], 500);
            }

            // Save current post context for dynamic mode display
            if (isset($formData['current_post_type']) && isset($formData['current_post_id'])) {
                update_post_meta($post_id, '_gdh_current_post_type', sanitize_text_field($formData['current_post_type']));
                update_post_meta($post_id, '_gdh_current_post_id', absint($formData['current_post_id']));
            }

            // Send notification email to receiver
            $receiverSent = false;
            try {
                $receiverSent = $this->emailService->sendOnAppointment($post_id, $formData);
                if ($receiverSent) {
                    update_post_meta($post_id, '_gdh_email_sent', '1');
                    $this->logger->info("GDH AJAX: Receiver notification sent successfully");
                } else {
                    $this->logger->error('GDH AJAX: Receiver notification email failed');
                }
            } catch (\Throwable $e) {
                $this->logger->error('GDH AJAX: Receiver email exception - ' . $e->getMessage());
            }

            // Send confirmation email to client if confirmation is enabled
            $confirmEnabled = get_option('gdh_email_confirm_enabled', '0') === '1';
            if ($confirmEnabled) {
                try {
                    $confirmSent = $this->emailService->sendConfirmationToClient($post_id, $formData);
                    if ($confirmSent) {
                        $this->logger->info("GDH AJAX: Client confirmation sent successfully");
                    } else {
                        $this->logger->error('GDH AJAX: Client confirmation email failed');
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('GDH AJAX: Client confirmation exception - ' . $e->getMessage());
                }
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
    

    
    /**
     * Security: Sanitize form data
     */
    private function sanitizeFormData($data)
    {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        
        // Sanitize text fields
        $text_fields = ['first_name', 'last_name', 'address', 'city', 'current_post_type'];
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }
        
        // Sanitize email
        $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
        
        // Sanitize phone and postal code
        $sanitized['phone'] = isset($data['phone']) ? preg_replace('/[^0-9+\-\s\(\)]/', '', $data['phone']) : '';
        $sanitized['postal_code'] = isset($data['postal_code']) ? preg_replace('/[^0-9A-Za-z\-\s]/', '', $data['postal_code']) : '';
        
        // Sanitize boolean
        $sanitized['accept_terms'] = isset($data['accept_terms']) ? (bool) $data['accept_terms'] : false;
        
        // Sanitize integers
        $sanitized['current_post_id'] = isset($data['current_post_id']) ? absint($data['current_post_id']) : 0;
        
        // Sanitize slots array
        $sanitized['slots'] = [];
        if (isset($data['slots']) && is_array($data['slots'])) {
            foreach ($data['slots'] as $slot) {
                if (is_array($slot)) {
                    $clean_slot = [
                        'date' => isset($slot['date']) ? sanitize_text_field($slot['date']) : '',
                        'times' => []
                    ];
                    
                    if (isset($slot['times']) && is_array($slot['times'])) {
                        foreach ($slot['times'] as $time) {
                            $clean_slot['times'][] = sanitize_text_field($time);
                        }
                    }
                    
                    $sanitized['slots'][] = $clean_slot;
                }
            }
        }
        
        return $sanitized;
    }
    

}
