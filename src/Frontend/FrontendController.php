<?php
namespace GDH\Frontend;

use GDH\Services\Logger;
use GDH\Services\TwigService;

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
        $plugin_root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR; // plugin root dir
        $css_path    = $plugin_root . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'frontend.css';
        $js_path     = $plugin_root . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'frontend.js';
        $css_ver     = file_exists($css_path) ? filemtime($css_path) : time();
        $js_ver      = file_exists($js_path) ? filemtime($js_path) : time();

        // Enqueue Dashicons for frontend
        wp_enqueue_style('dashicons');

        // Optional custom font URL from settings
        $opts = get_option('gdh_rdv_design_settings', []);
        if (is_array($opts) && ! empty($opts['font_url'])) {
            $font_url = esc_url($opts['font_url']);
            if (! empty($font_url)) {
                wp_enqueue_style('gdh-rdv-font', $font_url, [], null);
            }
        }

        wp_enqueue_style(
            'gdh-rdv-style',
            GDH_PLUGIN_URL . 'assets/css/frontend.css',
            ['dashicons'],
            $css_ver
        );

        // Inject dynamic CSS variables from settings
        $this->add_inline_design_variables();

        // Twig.js for client-side rendering of templates
        wp_enqueue_script(
            'twigjs',
            'https://cdn.jsdelivr.net/npm/twig@1.15.4/twig.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'gdh-rdv-script',
            GDH_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery', 'twigjs'],
            $js_ver,
            true
        );

        // Localize script with AJAX data
        $slot_tpl_path = GDH_PLUGIN_PATH . 'templates/frontend/cards/slot-card.twig';
        $slot_tpl      = file_exists($slot_tpl_path) ? file_get_contents($slot_tpl_path) : '';
        wp_localize_script(
            'gdh-rdv-script',
            'gdhRdvData',
            [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('gdh_rdv_nonce'),
                'templates' => [
                    'slotCard' => $slot_tpl,
                ],
            ]
        );
    }

    private function add_inline_design_variables()
    {
        $opts = get_option('gdh_rdv_design_settings', []);
        if (! is_array($opts)) {
            return;
        }

        $map = [
            '--gdh-primary-green'       => 'primary_color',
            '--gdh-primary-green-light' => 'primary_color_light',
            '--gdh-primary-green-dark'  => 'primary_color_dark',
            '--gdh-accent-yellow'       => 'accent_color',
            '--gdh-accent-yellow-dark'  => 'accent_color_dark',
            '--gdh-button-text-color'   => 'buttons_text_color',
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

        // Overlay RGB and opacity
        if (! empty($opts['overlay_color'])) {
            $hex    = sanitize_hex_color($opts['overlay_color']);
            $rgb    = $hex ? $this->hex_to_rgb_string($hex) : '0, 0, 0';
            $vars[] = '--gdh-overlay-rgb: ' . $rgb;
        }
        if (isset($opts['overlay_opacity'])) {
            $opacity = floatval($opts['overlay_opacity']);
            if ($opacity < 0) {
                $opacity = 0;
            }

            if ($opacity > 1) {
                $opacity = 1;
            }

            $vars[] = '--gdh-overlay-opacity: ' . $opacity;
        }

        // Font family (raw string)
        if (! empty($opts['font_family'])) {
            // sanitize_text_field already applied at save, but sanitize again
            $font   = sanitize_text_field($opts['font_family']);
            $vars[] = '--gdh-font-family: ' . $font;
        }

        if (! empty($vars)) {
            $css = ':root{' . implode(';', $vars) . ';}';
            wp_add_inline_style('gdh-rdv-style', $css);
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
