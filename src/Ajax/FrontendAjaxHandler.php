<?php
namespace GDHRDV\Ajax;

use GDHRDV\PostTypes\AppointmentPostType;
use GDHRDV\Services\EmailTemplateService;
use GDHRDV\Services\Logger;
use GDHRDV\Services\RecipientService;
use GDHRDV\Utils\Security;

/**
 * Gestionnaire AJAX spécifique au frontend
 */
class FrontendAjaxHandler
{
    private $logger;
    private $emailService;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->emailService = new EmailTemplateService($logger);
        $this->registerFrontendHooks();
    }

    private function registerFrontendHooks()
    {
        // Frontend AJAX actions (both logged in and non-logged in users)
        add_action('wp_ajax_gdhrdv_submit_appointment', [$this, 'handleSubmitAppointment']);
        add_action('wp_ajax_nopriv_gdhrdv_submit_appointment', [$this, 'handleSubmitAppointment']);
    }

    /**
     * Gère la soumission de rendez-vous depuis le frontend
     */
    public function handleSubmitAppointment()
    {
        try {
            if (!Security::validateFrontendRequest()) {
                wp_send_json_error(['message' => 'Requête invalide'], 400);
            }

            $formData = Security::validateJsonPayload($_POST['formData'] ?? '');
            if (!$formData) {
                wp_send_json_error(['message' => 'Données invalides'], 400);
            }

            $formData = Security::sanitizeFormData($formData);
            if (!Security::validateAppointmentData($formData)) {
                wp_send_json_error(['message' => 'Champs obligatoires manquants'], 400);
            }

            // Création du rendez-vous
            $post_id = AppointmentPostType::createAppointment($formData);
            if (!$post_id) {
                wp_send_json_error(['message' => 'Erreur lors de l\'enregistrement'], 500);
            }

            // Sauvegarde sécurisée des infos destinataire
            $this->saveRecipientInfo($post_id, $formData);

            // Envoi des emails
            $this->sendNotificationEmails($post_id, $formData);

            $this->logger->info("Frontend: RDV créé avec succès - ID: {$post_id}");

            wp_send_json_success([
                'message' => 'Votre demande de rendez-vous a été enregistrée avec succès !',
                'appointment_id' => $post_id,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Frontend: Échec soumission RDV - ' . $e->getMessage());
            wp_send_json_error(['message' => 'Une erreur inattendue est survenue'], 500);
        }
    }



    /**
     * Sauvegarde sécurisée des informations du destinataire
     */
    private function saveRecipientInfo($post_id, $formData)
    {
        $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
        if ($recipientInfo) {
            update_post_meta($post_id, '_gdhrdv_destinataire_email', $recipientInfo['email']);
            update_post_meta($post_id, '_gdhrdv_destinataire_name', $recipientInfo['name']);
        }

        // Sauvegarde du contexte de post actuel pour le mode dynamique
        if (isset($formData['current_post_type']) && isset($formData['current_post_id'])) {
            update_post_meta($post_id, '_gdhrdv_current_post_type', $formData['current_post_type']);
            update_post_meta($post_id, '_gdhrdv_current_post_id', $formData['current_post_id']);
        }
    }

    /**
     * Envoi des emails de notification
     */
    private function sendNotificationEmails($post_id, $formData)
    {
        // Envoi au destinataire
        try {
            $receiverSent = $this->emailService->sendOnAppointment($post_id, $formData);
            if ($receiverSent) {
                update_post_meta($post_id, '_gdhrdv_email_sent', '1');
            }
        } catch (\Throwable $e) {
            $this->logger->error('Frontend: Échec email destinataire - ' . $e->getMessage());
        }

        // Envoi confirmation au client si activé
        $confirmEnabled = get_option('gdhrdv_email_confirm_enabled', '0') === '1';
        if ($confirmEnabled) {
            try {
                $this->emailService->sendConfirmationToClient($post_id, $formData);
            } catch (\Throwable $e) {
                $this->logger->error('Frontend: Échec confirmation client - ' . $e->getMessage());
            }
        }
    }
}
