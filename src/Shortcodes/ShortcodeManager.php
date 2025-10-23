<?php
namespace GDHRDV\Shortcodes;

use GDHRDV\Services\Logger;
use GDHRDV\Services\TwigService;
use GDHRDV\Services\RecipientService;

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
        add_shortcode('GDH_RDV', [$this, 'renderShortcode']);
    }

    public function renderShortcode($atts)
    {
        $atts = shortcode_atts([
            'button_label' => 'Prendre rendez-vous',
            'class'        => "",
            'style'        => "",
        ],
            $atts,
            'GDH_RDV');

        $label = sanitize_text_field($atts['button_label']);
        $class = sanitize_html_class($atts['class']);
        $style = sanitize_text_field($atts['style']);

        // Lit les paramètres d'apparence (titre) depuis l'administration
        $opts        = get_option('gdhrdv_design_settings', []);
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

        // Détecte automatiquement le type de contenu courant pour le destinataire dynamique
        $current_post_type = '';
        $current_post_id   = 0;
        if (is_singular()) {
            global $post;
            if ($post) {
                $current_post_type = get_post_type($post);
                $current_post_id   = $post->ID;
            }
        }

        // Génère un ID de popup unique et prédictible
        $base_id  = substr(uniqid('', false), -6);
        $popup_id = 'gdhrdv-rdv-popup-' . $base_id;

        // Valide la configuration
        $config_error = $this->validateConfiguration($current_post_type);

        // Récupère les informations du destinataire à transmettre au template
        $recipient_email = '';
        $recipient_name  = '';

        if (! $config_error) {
            $recipientInfo = $this->getRecipientInfo($current_post_type, $current_post_id);
            if ($recipientInfo) {
                $recipient_email = $recipientInfo['email'];
                $recipient_name  = $recipientInfo['name'];
            } else {
                // Si le destinataire ne peut pas être déterminé, bloquer le bouton avec un message d'aide
                $config_error = $this->createErrorMessage(
                    'Configuration requise : Aucun destinataire valide n\'a été détecté. Veuillez configurer l\'e-mail du destinataire (mode statique) ou renseigner les métadonnées sur le contenu (mode dynamique).'
                );
            }
        }

        // Si une erreur de configuration existe mais que l'utilisateur ne doit pas la voir, ne rien afficher
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
            'popup_id'          => $popup_id,
        ]);

        if (trim($html) === '') {
            $this->logger->error('GDHRDV : le rendu du shortcode est vide');
        }

        echo $html;
    }

    /**
     * Récupère les informations du destinataire via le service centralisé
     */
    private function getRecipientInfo($current_post_type, $current_post_id)
    {
        $formData = [
            'current_post_type' => $current_post_type,
            'current_post_id' => $current_post_id
        ];
        
        $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
        if ($recipientInfo) {
            $this->logger->info("GDHRDV Shortcode : destinataire utilisé - {$recipientInfo['name']} ({$recipientInfo['email']})");
        } else {
            $this->logger->error('GDHRDV Shortcode : aucun destinataire valide configuré');
        }
        
        return $recipientInfo;
    }

    /**
     * Crée un message d'erreur avec un lien vers les réglages (seulement pour les administrateurs ou en mode debug)
     */
    private function createErrorMessage($message)
    {
        // Afficher les messages détaillés uniquement aux administrateurs ou en mode debug
        if (current_user_can('manage_options') || (defined('WP_DEBUG') && WP_DEBUG)) {
            return sprintf(
                '%s Veuillez <a href="%s" style="color:#0073aa;text-decoration:underline;">accéder aux paramètres</a>.',
                $message,
                esc_url(admin_url('admin.php?page=gdhrdv_email_settings'))
            );
        }
        
        // Pour les visiteurs, retourner null afin de masquer complètement le shortcode
        return null;
    }
    
    /**
     * Valide la configuration complète
     *
     * @param string $detected_post_type Type de contenu détecté où le shortcode est placé
     * @return string|false Message d'erreur si la configuration est invalide, false si valide
     */
    private function validateConfiguration($detected_post_type)
    {
        $receivers = get_option('gdhrdv_receivers', []);
        $staticEnabled = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';
        
        // Si au moins un mode est configuré, valider le destinataire et les modèles d'e‑mail
        if ($staticEnabled || $dynamicEnabled) {
            // Valider la configuration du destinataire
            $recipientError = $this->validateRecipientConfiguration($detected_post_type);
            if ($recipientError) {
                return $recipientError;
            }
            
            // Valider les modèles d'e‑mail
            return $this->validateEmailTemplates();
        }
        
        // Aucun mode configuré : fallback (pas de validation nécessaire)
        return false;
    }
    
    /**
     * Valide la configuration du destinataire
     *
     * @param string $detected_post_type Type de contenu détecté où le shortcode est placé
     * @return string|false Message d'erreur si la configuration est invalide, false si valide
     */
    private function validateRecipientConfiguration($detected_post_type)
    {
        $receivers = get_option('gdhrdv_receivers', []);

        // Vérifier si au moins un mode est activé
        $staticEnabled  = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';

        // Si aucun mode n'est sélectionné, utiliser la valeur par défaut (email du site) — pas d'erreur
        if (!$staticEnabled && !$dynamicEnabled) {
            return false;
        }

        // Validation du mode statique
        if ($staticEnabled) {
            $email = isset($receivers['static']['email']) ? trim($receivers['static']['email']) : '';
            if (!$email || !is_email($email)) {
                return $this->createErrorMessage(
                    'Configuration requise : L\'adresse email du destinataire statique n\'est pas valide.'
                );
            }
        }

        // Validation du mode dynamique
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
            
            // Vérifier si le contexte courant correspond à la configuration dynamique
            if ($detected_post_type && $detected_post_type !== $configuredPostType) {
                return $this->createErrorMessage(
                    'Configuration requise : Ce shortcode est configuré pour le type de contenu "' . $configuredPostType . '" mais se trouve sur un contenu de type "' . $detected_post_type . '".'
                );
            }
        }

        return false;
    }
    
    /**
     * Valide les modèles d'e‑mail
     *
     * @return string|false Message d'erreur si les modèles sont invalides, false si valides
     */
    private function validateEmailTemplates()
    {
        $subject = get_option('gdhrdv_email_subject', '');
        $body = get_option('gdhrdv_email_body', '');
        
        if (empty($subject) || empty($body)) {
            return $this->createErrorMessage(
                'Configuration requise : Les templates d\'email (sujet et corps) ne sont pas configurés.'
            );
        }
        
        return false;
    }
}
