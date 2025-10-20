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

        $html = $this->twig->render('frontend/popup.twig', [
            'button_label' => $label,
            'class'        => $class,
            'style'        => $style,
            'title_text'   => $title_text,
            'title_align'  => $title_align,
            'cgv_url'      => $cgv_url,
        ]);

        if (trim($html) === '') {
            $this->logger->error('GDH: Shortcode render returned empty HTML');
        }

        return $html;
    }
}
