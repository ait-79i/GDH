<?php
namespace GDH\Frontend;

use GDH\Services\Logger;
use GDH\Services\TwigService;

class FrontendController{
    private $logger;
    private $twig;

    public function __construct (Logger $logger, TwigService $twig){
        $this->logger = $logger;
        $this->twig = $twig;
        $this->init();
    }

    private function init(){
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_scripts']);
    }

    public function enqueue_front_scripts(){
        wp_enqueue_style(
            'gdh-rdv-style',
            GDH_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'gdh-rdv-script',
            GDH_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}