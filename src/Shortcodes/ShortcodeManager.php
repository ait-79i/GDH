<?php
namespace GDH\Shortcodes;

use GDH\Services\Logger;
use GDH\Services\TwigService;
use GDH\Services\RecipientService;

class ShortcodeManager
{
    private $logger;
    private $twig;

    public function __construct(Logger $logger, TwigService $twig)
    {
        $this->logger = $logger;
        $this->twig   = $twig;

        $this->registerShortcodes();
    }

    public function registerShortcodes()
    {
        add_shortcode('gdh_rdv', [$this, 'renderShortcode']);
    }

    public function renderShortcode($atts)
    {
        $atts = shortcode_atts([
            'button_label' => 'Prendre rendez-vous',
            'class'        => "",
            'style'        => "",
        ],
            $atts,
            'gdh_rdv');

        $label = sanitize_text_field($atts['button_label']);
        $class = sanitize_html_class($atts['class']);
        $style = sanitize_text_field($atts['style']);

        // Read admin design settings for title
        $opts        = get_option('gdh_rdv_design_settings', []);
        $title_text  = isset($opts['title_text']) ? sanitize_text_field($opts['title_text']) : 'Prendre rendez-vous';
        $align_raw   = isset($opts['title_align']) ? $opts['title_align'] : 'left';
        $allowed     = ['left', 'center', 'right'];
        $title_align = in_array($align_raw, $allowed, true) ? $align_raw : 'left';
        $cgv_url     = '';
        $cgv_id      = isset($opts['cgv_page_id']) ? absint($opts['cgv_page_id']) : 0;
        if ($cgv_id) {
            $link = get_permalink($cgv_id);
            if ($link) {
                $cgv_url = esc_url($link);
            }
        }

        // Auto-detect current post type for dynamic recipient
        $current_post_type = '';
        $current_post_id   = 0;
        if (is_singular()) {
            global $post;
            if ($post) {
                $current_post_type = get_post_type($post);
                $current_post_id   = $post->ID;
            }
        }

        // Validate configuration
        $config_error = $this->validateConfiguration($current_post_type);

        // Get recipient information to pass to template
        $recipient_email = '';
        $recipient_name  = '';

        if (! $config_error) {
            $recipientInfo = $this->getRecipientInfo($current_post_type, $current_post_id);
            if ($recipientInfo) {
                $recipient_email = $recipientInfo['email'];
                $recipient_name  = $recipientInfo['name'];
            } else {
                // When recipient cannot be resolved, block the button with guidance
                $config_error = $this->createErrorMessage(
                    'Configuration requise : Aucun destinataire valide n\'a été détecté. Veuillez configurer l\'e-mail du destinataire (mode statique) ou renseigner les métadonnées sur le contenu (mode dynamique).'
                );
            }
        }

        // If config error exists but user can't see it, don't render anything
        if ($config_error === null) {
            return '';
        }
        
        $html = $this->twig->render('frontend/popup.twig', [
            'button_label'      => $label,
            'class'             => $class,
            'style'             => $style,
            'title_text'        => $title_text,
            'title_align'       => $title_align,
            'cgv_url'           => $cgv_url,
            'current_post_type' => $current_post_type,
            'current_post_id'   => $current_post_id,
            'config_error'      => $config_error,
            'recipient_email'   => $recipient_email,
            'recipient_name'    => $recipient_name,
        ]);

        if (trim($html) === '') {
            $this->logger->error('GDH: Shortcode render returned empty HTML');
        }

        echo $html;
    }

    /**
     * Get recipient information using centralized service
     */
    private function getRecipientInfo($current_post_type, $current_post_id)
    {
        $formData = [
            'current_post_type' => $current_post_type,
            'current_post_id' => $current_post_id
        ];
        
        $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
        if ($recipientInfo) {
            $this->logger->info("GDH Shortcode: Using recipient - {$recipientInfo['name']} ({$recipientInfo['email']})");
        } else {
            $this->logger->error('GDH Shortcode: No valid recipient configured');
        }
        
        return $recipientInfo;
    }

    /**
     * Create error message with settings link (only for admins or debug mode)
     */
    private function createErrorMessage($message)
    {
        // Only show detailed error messages to administrators or in debug mode
        if (current_user_can('manage_options') || (defined('WP_DEBUG') && WP_DEBUG)) {
            return sprintf(
                '%s Veuillez <a href="%s" style="color:#0073aa;text-decoration:underline;">accéder aux paramètres</a>.',
                $message,
                esc_url(admin_url('admin.php?page=gdh_email_settings'))
            );
        }
        
        // For regular users, return null to hide the shortcode completely
        return null;
    }
    
    /**
     * Validate complete configuration
     *
     * @param string $detected_post_type The detected post type where shortcode is placed
     * @return string|false Error message if configuration is invalid, false if valid
     */
    private function validateConfiguration($detected_post_type)
    {
        $receivers = get_option('gdh_receivers', []);
        $staticEnabled = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';
        
        // If modes are configured, validate them and templates
        if ($staticEnabled || $dynamicEnabled) {
            // Validate recipient configuration
            $recipientError = $this->validateRecipientConfiguration($detected_post_type);
            if ($recipientError) {
                return $recipientError;
            }
            
            // Validate email templates
            return $this->validateEmailTemplates();
        }
        
        // No modes configured, using fallback - no validation needed
        return false;
    }
    
    /**
     * Validate recipient configuration
     *
     * @param string $detected_post_type The detected post type where shortcode is placed
     * @return string|false Error message if configuration is invalid, false if valid
     */
    private function validateRecipientConfiguration($detected_post_type)
    {
        $receivers = get_option('gdh_receivers', []);

        // Check if any mode is enabled
        $staticEnabled  = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';

        // If no mode selected, use default (site email) - no error
        if (!$staticEnabled && !$dynamicEnabled) {
            return false;
        }

        // Static mode validation
        if ($staticEnabled) {
            $email = isset($receivers['static']['email']) ? trim($receivers['static']['email']) : '';
            if (!$email || !is_email($email)) {
                return $this->createErrorMessage(
                    'Configuration requise : L\'adresse email du destinataire statique n\'est pas valide.'
                );
            }
        }

        // Dynamic mode validation
        if ($dynamicEnabled) {
            $configuredPostType = isset($receivers['dynamic']['post_type']) ? $receivers['dynamic']['post_type'] : '';
            $emailMetaKey = isset($receivers['dynamic']['email']) ? trim($receivers['dynamic']['email']) : '';
            
            if (!$configuredPostType) {
                return $this->createErrorMessage(
                    'Configuration requise : Le type de contenu pour le mode dynamique n\'est pas configuré.'
                );
            }
            
            if (!$emailMetaKey) {
                return $this->createErrorMessage(
                    'Configuration requise : La clé méta pour l\'email en mode dynamique n\'est pas configurée.'
                );
            }
            
            // Check if current context matches dynamic configuration
            if ($detected_post_type && $detected_post_type !== $configuredPostType) {
                return $this->createErrorMessage(
                    'Configuration requise : Ce shortcode est configuré pour le type de contenu "' . $configuredPostType . '" mais se trouve sur un contenu de type "' . $detected_post_type . '".'
                );
            }
        }

        return false;
    }
    
    /**
     * Validate email templates
     *
     * @return string|false Error message if templates are invalid, false if valid
     */
    private function validateEmailTemplates()
    {
        $subject = get_option('gdh_email_subject', '');
        $body = get_option('gdh_email_body', '');
        
        if (empty($subject) || empty($body)) {
            return $this->createErrorMessage(
                'Configuration requise : Les templates d\'email (sujet et corps) ne sont pas configurés.'
            );
        }
        
        return false;
    }
}