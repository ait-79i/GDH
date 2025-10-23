<?php
namespace GDHRDV\Ajax;

use GDHRDV\Services\Logger;
use GDHRDV\Utils\Security;

/**
 * Gestionnaire AJAX spécifique à l'administration
 */
class AdminAjaxHandler
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->registerAdminHooks();
    }

    private function registerAdminHooks()
    {
        // Admin-only AJAX actions
        add_action('wp_ajax_gdhrdv_get_meta_keys', [$this, 'handleGetMetaKeys']);
        add_action('wp_ajax_gdhrdv_resend_email', [$this, 'handleResendEmail']);
    }

    /**
     * Gère la récupération des clés meta pour l'admin
     */
    public function handleGetMetaKeys()
    {
        if (!Security::validateAdminRequest('gdhrdv_meta_keys')) {
            wp_send_json_error(['message' => 'Accès non autorisé'], 403);
        }

        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
        if (!$post_type || !post_type_exists($post_type)) {
            wp_send_json_error(['message' => 'Type de contenu invalide'], 400);
        }

        global $wpdb;
        $keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.meta_key
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE p.post_type = %s
                   AND pm.meta_key IS NOT NULL
                   AND pm.meta_key <> ''
                 ORDER BY pm.meta_key ASC
                 LIMIT 200",
                $post_type
            )
        );

        $keys = is_array($keys) ? array_map('sanitize_key', $keys) : [];
        wp_send_json_success(['meta_keys' => array_values($keys)]);
    }

    /**
     * Gère le renvoi d'email pour l'admin
     */
    public function handleResendEmail()
    {
        if (!Security::validateAdminRequest('gdhrdv_resend_email')) {
            wp_send_json_error(['message' => 'Accès non autorisé'], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if (!$post_id || get_post_type($post_id) !== 'gdhrdv_appointment') {
            wp_send_json_error(['message' => 'Rendez-vous invalide'], 400);
        }

        try {
            $result = \GDHRDV\PostTypes\AppointmentPostType::resendEmail($post_id);
            
            if ($result) {
                $this->logger->info("Admin: Email renvoyé pour le RDV #{$post_id}");
                wp_send_json_success(['message' => 'Email renvoyé avec succès']);
            } else {
                wp_send_json_error(['message' => 'Échec de l\'envoi de l\'email']);
            }
        } catch (\Exception $e) {
            $this->logger->error("Admin: Échec renvoi email - " . $e->getMessage());
            wp_send_json_error(['message' => 'Erreur lors de l\'envoi']);
        }
    }


}
