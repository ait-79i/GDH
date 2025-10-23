<?php
namespace GDHRDV\Core;

use GDHRDV\Admin\AdminController;
use GDHRDV\Ajax\AdminAjaxHandler;
use GDHRDV\Ajax\FrontendAjaxHandler;
use GDHRDV\Frontend\FrontendController;
use GDHRDV\PostTypes\AppointmentPostType;
use GDHRDV\Services\Logger;
use GDHRDV\Services\TwigService;
use GDHRDV\Shortcodes\ShortcodeManager;

/**
 * Classe principale du plugin
 */
class Plugin
{
    private static $instance = null;
    private $logger;
    private $twig;

    /**
     * Récupère l'instance du plugin
     *
     * @return Plugin
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialise le plugin
     */
    public function init()
    {
        $this->logger = new Logger();
        $this->twig   = new TwigService();

        // Enregistre les types de contenu personnalisés
        new AppointmentPostType();

        // Enregistre les gestionnaires AJAX séparés
        new FrontendAjaxHandler($this->logger);
        
        if (is_admin()) {
            new AdminController();
            new AdminAjaxHandler($this->logger);
        } else {
            new FrontendController($this->logger, $this->twig);
        }

        new ShortcodeManager($this->logger, $this->twig);
    }

    /**
     * Active le plugin
     */
    public static function activate()
    {
        (new AppointmentPostType())->register();
        flush_rewrite_rules();
    }

    /**
     * Désactive le plugin
     */
    public static function deactivate()
    {
        // Actions de désactivation
        // Vide les règles de réécriture lors de la désactivation
        flush_rewrite_rules();
    }
}
