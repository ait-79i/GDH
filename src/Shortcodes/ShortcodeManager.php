<?php
namespace GDH\Shortcodes;

use GDH\Services\Logger;
use GDH\Services\TwigService;

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

        return $html;
    }

    /**
     * Get recipient information based on configuration
     *
     * @param string $current_post_type The current post type where shortcode is placed
     * @param int $current_post_id The current post ID
     * @return array|false Array with 'email' and 'name' keys, or false if not configured
     */
    private function getRecipientInfo($current_post_type, $current_post_id)
    {
        $receivers = get_option('gdh_receivers', []);

        // Check if static receiver is enabled
        $staticEnabled = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        if ($staticEnabled) {
            $email = isset($receivers['static']['email']) ? trim($receivers['static']['email']) : '';
            $name  = isset($receivers['static']['name']) ? trim($receivers['static']['name']) : '';

            if ($email && is_email($email)) {
                return [
                    'email' => $email,
                    'name'  => $name ?: get_bloginfo('name'),
                ];
            }
        }

        // Check if dynamic receiver is enabled
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';
        if ($dynamicEnabled) {
            $configuredPostType = isset($receivers['dynamic']['post_type']) ? $receivers['dynamic']['post_type'] : '';
            $emailMetaKey       = isset($receivers['dynamic']['email']) ? trim($receivers['dynamic']['email']) : '';
            $nameMetaKey        = isset($receivers['dynamic']['name']) ? trim($receivers['dynamic']['name']) : '';
            // Auto-detect: use current post if it matches configured post type
            if ($current_post_type && $current_post_id && $current_post_type === $configuredPostType) {
                $email = trim(get_post_meta($current_post_id, $emailMetaKey, true));
                $name  = trim(get_post_meta($current_post_id, $nameMetaKey, true));

                if ($email && is_email($email)) {
                    $this->logger->info("GDH Shortcode: Using dynamic recipient from post ID {$current_post_id} (type: {$current_post_type})");
                    return [
                        'email' => $email,
                        'name'  => $name ?: get_bloginfo('name'),
                    ];
                }
            }
        }

        // Default fallback: use site email and name if no configuration
        $siteEmail = get_option('admin_email');
        $siteName = get_bloginfo('name');
        
        if ($siteEmail && is_email($siteEmail)) {
            $this->logger->info('GDH Shortcode: Using default site email as fallback');
            return [
                'email' => $siteEmail,
                'name'  => $siteName,
            ];
        }

        $this->logger->error('GDH Shortcode: No valid recipient configured');
        return false;
    }

    /**
     * Create error message with settings link
     */
    private function createErrorMessage($message)
    {
        return sprintf(
            '%s Veuillez <a href="%s" style="color:#0073aa;text-decoration:underline;">accéder aux paramètres</a>.',
            $message,
            esc_url(admin_url('admin.php?page=gdh_email_settings'))
        );
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
        if (! $staticEnabled && ! $dynamicEnabled) {
            return false; // Allow default fallback
        }

        // Static mode validation
        if ($staticEnabled) {
            $email = isset($receivers['static']['email']) ? trim($receivers['static']['email']) : '';
            $name  = isset($receivers['static']['name']) ? trim($receivers['static']['name']) : '';

            if (empty($email) || ! is_email($email)) {
                return $this->createErrorMessage('Configuration incomplète : L\'adresse e-mail du destinataire statique est manquante ou invalide.');
            }

            if (empty($name)) {
                return $this->createErrorMessage('Configuration incomplète : Le nom du destinataire statique est manquant.');
            }
        }

        // Dynamic mode validation
        if ($dynamicEnabled) {
            $configuredPostType = isset($receivers['dynamic']['post_type']) ? $receivers['dynamic']['post_type'] : '';
            $emailMetaKey       = isset($receivers['dynamic']['email']) ? trim($receivers['dynamic']['email']) : '';
            $nameMetaKey        = isset($receivers['dynamic']['name']) ? trim($receivers['dynamic']['name']) : '';
            
            // Check if post type is configured
            if (empty($configuredPostType)) {
                return $this->createErrorMessage('Configuration incomplète : Le type de contenu pour le destinataire dynamique n\'est pas sélectionné.');
            }
            
            // Check if meta keys are configured
            if (empty($emailMetaKey) || empty($nameMetaKey)) {
                return $this->createErrorMessage('Configuration incomplète : Les champs meta pour l\'e-mail ou le nom du destinataire dynamique ne sont pas configurés.');
            }

            // If shortcode is not rendered in a singular context (no detected post type), block usage with guidance
            if (empty($detected_post_type)) {
                return $this->createErrorMessage(sprintf(
                    'Configuration requise : Le destinataire dynamique nécessite que le shortcode soit placé dans un contenu du type configuré et placer le shortcode sur un contenu de type "%s".',
                    esc_html($configuredPostType)
                ));
            }
            
            // Check if detected post type matches configured post type
            if ($detected_post_type && $detected_post_type !== $configuredPostType) {
                $post_types     = get_post_types(['show_ui' => true], 'objects');
                $detected_label = isset($post_types[$detected_post_type]) 
                    ? $post_types[$detected_post_type]->labels->singular_name 
                    : $detected_post_type;
                $configured_label = isset($post_types[$configuredPostType]) 
                    ? $post_types[$configuredPostType]->labels->singular_name 
                    : $configuredPostType;
                
                return $this->createErrorMessage(sprintf(
                    'Incompatibilité de type de contenu : Le shortcode est placé dans un contenu de type "%s" mais le destinataire dynamique est configuré pour "%s". Ajuster la configuration ou déplacer le shortcode.',
                    esc_html($detected_label),
                    esc_html($configured_label)
                ));
            }
        }

        return false; // No error
    }
    
    /**
     * Validate email templates configuration
     *
     * @return string|false Error message if templates are invalid, false if valid
     */
    private function validateEmailTemplates()
    {
        $subject = (string) get_option('gdh_email_subject', '');
        $body = (string) get_option('gdh_email_body', '');
        
        if (empty($subject) && empty($body)) {
            return $this->createErrorMessage('Templates d\'emails manquants : Les templates d\'emails ne sont pas configurés.');
        }
        
        if (empty($subject)) {
            return $this->createErrorMessage('Template incomplet : Le sujet de l\'email artisan est manquant.');
        }
        
        if (empty($body)) {
            return $this->createErrorMessage('Template incomplet : Le corps de l\'email artisan est manquant.');
        }
        
        return false; // No error
    }
}
