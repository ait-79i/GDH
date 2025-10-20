<?php
namespace GDH\Services;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigService
{
    private $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(GDH_PLUGIN_PATH . 'templates');

        $cachePath = GDH_PLUGIN_PATH . 'cache/twig';
        // Ensure cache directory exists; if not, disable caching to avoid exceptions
        $cacheEnabled = false;
        if (! defined('WP_DEBUG') || WP_DEBUG === false) {
            if (! file_exists($cachePath)) {
                // Attempt to create cache directory
                if (function_exists('wp_mkdir_p')) {
                    wp_mkdir_p($cachePath);
                } else {
                    @mkdir($cachePath, 0755, true);
                }
            }
            if (is_dir($cachePath) && is_writable($cachePath)) {
                $cacheEnabled = $cachePath;
            }
        }

        $this->twig = new Environment($loader, [
            'cache' => $cacheEnabled ?: false,
            'debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
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
