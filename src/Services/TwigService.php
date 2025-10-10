<?php
namespace GDH\Services;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigService
{
    private $twig;

    public function __construct(){
        $loader = new FilesystemLoader(GDH_PLUGIN_PATH . 'templates');
        $this->twig = new Environment($loader,[
            'cache'=> GDH_PLUGIN_PATH . 'cache/twig',
            'debug' => WP_DEBUG,
        ]);

    }

    public function render($template, $data = [])
    {
        try {
            return $this->twig->render($template, $data);
        } catch (\Exception $e) {
            error_log('Erreur Twig: ' . $e->getMessage());
            return '';
        }
    }

    public function getTwig()
    {
        return $this->twig;
    }
}