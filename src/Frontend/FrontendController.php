<?php
namespace GDHRDV\Frontend;

use GDHRDV\Services\Logger;
use GDHRDV\Services\TwigService;

class FrontendController
{
    private $logger;
    private $twig;

    public function __construct(Logger $logger, TwigService $twig)
    {
        $this->logger = $logger;
        $this->twig   = $twig;
        $this->init();
    }

    private function init()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_scripts'], 99);
    }

    public function enqueue_front_scripts()
    {
        $plugin_root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR; // répertoire racine du plugin
        $css_path    = $plugin_root . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'frontend.css';
        $js_path     = $plugin_root . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'frontend.js';
        $css_ver     = file_exists($css_path) ? filemtime($css_path) : time();
        $js_ver      = file_exists($js_path) ? filemtime($js_path) : time();

        // Charge Dashicons pour le frontend
        wp_enqueue_style('dashicons');

        // URL de police personnalisée optionnelle depuis les réglages
        $opts = get_option('gdhrdv_design_settings', []);
        if (is_array($opts) && ! empty($opts['font_url'])) {
            $font_url = esc_url($opts['font_url']);
            if (! empty($font_url)) {
                wp_enqueue_style('gdhrdv-font', $font_url, [], null);
            }
        }

        wp_enqueue_style(
            'gdhrdv-style',
            GDHRDV_PLUGIN_URL . 'assets/css/frontend.css',
            ['dashicons'],
            $css_ver
        );

        // Injecte des variables CSS dynamiques depuis les réglages
        $this->add_inline_design_variables();

        // Twig.js pour le rendu côté client des templates
        wp_enqueue_script(
            'twigjs',
            'https://cdn.jsdelivr.net/npm/twig@1.15.4/twig.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'gdhrdv-script',
            GDHRDV_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'twigjs'],
            $js_ver,
            true
        );

        // Localise le script avec les données AJAX
        $slot_tpl_path = GDHRDV_PLUGIN_PATH . 'templates/frontend/cards/slot-card.twig';
        $slot_tpl      = file_exists($slot_tpl_path) ? file_get_contents($slot_tpl_path) : '';
        wp_localize_script(
            'gdhrdv-script',
            'gdhrdvData',
            [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('gdhrdv_nonce'),
                'templates' => [
                    'slotCard' => $slot_tpl,
                ],
                'debug'     => (defined('WP_DEBUG') && WP_DEBUG),
            ]
        );
    }

    private function add_inline_design_variables()
    {
        $opts = get_option('gdhrdv_design_settings', []);
        if (! is_array($opts)) {
            return;
        }

        $map = [
            '--gdhrdv-primary-green'       => 'primary_color',
            '--gdhrdv-primary-green-light' => 'primary_color_light',
            '--gdhrdv-primary-green-dark'  => 'primary_color_dark',
            '--gdhrdv-accent-yellow'       => 'accent_color',
            '--gdhrdv-accent-yellow-dark'  => 'accent_color_dark',
            '--gdhrdv-button-text-color'   => 'buttons_text_color',
        ];

        $vars = [];
        foreach ($map as $cssVar => $key) {
            if (! empty($opts[$key])) {
                $color = sanitize_hex_color($opts[$key]);
                if ($color) {
                    $vars[] = $cssVar . ': ' . $color;
                }
            }
        }

        // Couleur d'overlay en RGB et opacité
        if (! empty($opts['overlay_color'])) {
            $hex    = sanitize_hex_color($opts['overlay_color']);
            $rgb    = $hex ? $this->hex_to_rgb_string($hex) : '0, 0, 0';
            $vars[] = '--gdhrdv-overlay-rgb: ' . $rgb;
        }
        if (isset($opts['overlay_opacity'])) {
            $opacity = floatval($opts['overlay_opacity']);
            if ($opacity < 0) {
                $opacity = 0;
            }

            if ($opacity > 1) {
                $opacity = 1;
            }

            $vars[] = '--gdhrdv-overlay-opacity: ' . $opacity;
        }

        // Famille de police (chaîne brute)
        if (! empty($opts['font_family'])) {
            // sanitize_text_field already applied at save, but sanitize again
            $font   = sanitize_text_field($opts['font_family']);
            $vars[] = '--gdhrdv-font-family: ' . $font;
        }

        if (! empty($vars)) {
            $css = ':root{' . implode(';', $vars) . ';}';
            wp_add_inline_style('gdhrdv-style', $css);
        }
    }

    private function hex_to_rgb_string($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } elseif (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            return '0, 0, 0';
        }
        return sprintf('%d, %d, %d', $r, $g, $b);
    }
}
