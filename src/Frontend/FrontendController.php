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

        wp_enqueue_style(
            'gdh-rdv-style',
            GDH_PLUGIN_URL . 'assets/css/frontend.css',
            ['dashicons'],
            $css_ver
        );

        wp_enqueue_script(
            'gdh-rdv-script',
            GDH_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            $js_ver,
            true
        );

        // Localize script with AJAX data
        wp_localize_script(
            'gdh-rdv-script',
            'gdhRdvData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('gdh_rdv_nonce')
            ]
        );
    }
}
