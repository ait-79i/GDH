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
            'class'        => "gdh-rdv-btn",
            'style'        => "",
        ],
            $atts,
            'gdh_rdv');

        $label = sanitize_text_field($atts['button_label']);
        $class = sanitize_html_class($atts['class']);
        $style = sanitize_text_field($atts['style']);

        $html = $this->twig->render('shortcodes/popup.twig', [
            'button_label' => $label,
            'class'   => $class,
            'style'   => $style,

        ]);

        if (trim($html) === '') {
            $this->logger->error('GDH: Shortcode render returned empty HTML');
        }

        return $html;
    }
}
