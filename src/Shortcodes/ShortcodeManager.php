<?php

namespace GDH\Shortcodes;

use GDH\Services\Logger;
use GDH\Services\TwigService;

class ShortcodeManger {

    private $logger;
    private $twig;

    public function __construct(Logger $logger,TwigService $twig){
        $this->logger = $logger;
        $this->twig = $twig;

        $this->registerShorcodes();
    }

    public function registerShorcodes(){
        add_shortcode( 'gdh_rdv', [$this,'render_shortcode'] );
    }

    public function render_shortcode($atts){
        $atts = shortcode_atts( [
            'gdh_rdv' => "Prendre rendez-vous",
            'class' => "gdh-rdv-btn",
            'style' => "primary",
        ],
        $atts,
        'gdh_rdv' );

          return $this->twig->render('shortcodes/popup.twig', [
            'gdh_rdv' => $atts['gdh_rdv'],
            'class' => sanitize_html_class($atts['class']),
            'style' => sanitize_html_class($atts['style']),
            
        ]);
    }
}