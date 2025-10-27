<?php
namespace GDHRDV\Utils;

/**
 * Classe utilitaire de sécurité pour les validations centralisées
 */
class Security
{
    /**
     * Valide les capacités admin et le nonce
     */
    public static function validateAdminRequest($nonce_action, $nonce_field = 'nonce')
    {
        return current_user_can('manage_options') && 
               check_ajax_referer($nonce_action, $nonce_field, false);
    }

    /**
     * Valide le nonce frontend
     */
    public static function validateFrontendRequest($nonce_action = 'gdhrdv_nonce', $nonce_field = 'nonce')
    {
        return wp_verify_nonce($_POST[$nonce_field] ?? '', $nonce_action);
    }

    /**
     * Nettoie les données du formulaire
     */
    public static function sanitizeFormData($data)
    {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [];
        
        // Champs texte
        $text_fields = ['first_name', 'last_name', 'address', 'city', 'current_post_type'];
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : '';
        }
        
        // Message (textarea avec limite de caractères)
        $sanitized['message'] = '';
        if (isset($data['message']) && is_string($data['message'])) {
            $message = wp_strip_all_tags($data['message']); // Retire les balises HTML
            $message = substr($message, 0, 500); // Limite à 500 caractères
            $sanitized['message'] = sanitize_textarea_field($message);
        }
        
        // Email
        $sanitized['email'] = isset($data['email']) ? sanitize_email($data['email']) : '';
        
        // Téléphone et code postal
        $sanitized['phone'] = isset($data['phone']) ? preg_replace('/[^0-9+\-\s\(\)]/', '', $data['phone']) : '';
        $sanitized['postal_code'] = isset($data['postal_code']) ? preg_replace('/[^0-9A-Za-z\-\s]/', '', $data['postal_code']) : '';
        
        // Booléen
        $sanitized['accept_terms'] = isset($data['accept_terms']) ? (bool) $data['accept_terms'] : false;
        
        // Entier
        $sanitized['current_post_id'] = isset($data['current_post_id']) ? absint($data['current_post_id']) : 0;
        
        // Tableau des créneaux
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

    /**
     * Valide les champs obligatoires du rendez-vous
     */
    public static function validateAppointmentData($data)
    {
        $required = ['first_name', 'last_name', 'email', 'phone', 'address', 'postal_code', 'city'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        if (!is_email($data['email'])) {
            return false;
        }

        if (empty($data['slots']) || !is_array($data['slots'])) {
            return false;
        }

        // Vérifie l'acceptation des CGV si configurées (via gdhrdv_design_settings)
        $design_opts = get_option('gdhrdv_design_settings', []);
        $cgvPageId = is_array($design_opts) && isset($design_opts['cgv_page_id']) ? (string) $design_opts['cgv_page_id'] : '';
        if ($cgvPageId && !$data['accept_terms']) {
            return false;
        }

        return true;
    }

    /**
     * Valide la taille et le format du payload JSON
     */
    public static function validateJsonPayload($rawData, $maxSize = 10000)
    {
        if (strlen($rawData) > $maxSize) {
            return false;
        }

        $data = json_decode(wp_unslash($rawData), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $data;
    }
}
