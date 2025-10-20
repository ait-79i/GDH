<?php
namespace GDH\Core;

use GDH\Admin\AdminController;
use GDH\Ajax\AppointmentAjaxHandler;
use GDH\Frontend\FrontendController;
use GDH\PostTypes\AppointmentPostType;
use GDH\Services\Logger;
use GDH\Services\TwigService;
use GDH\Shortcodes\ShortcodeManager;

/**
 * Main plugin class
 */
class Plugin
{
    private static $instance = null;
    private $logger;
    private $twig;

    /**
     * Get the instance of the plugin
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
     * Initialize the plugin
     */
    public function init()
    {
        $this->logger = new Logger();
        $this->twig   = new TwigService();

        // Register custom post types
        new AppointmentPostType();

        // Register AJAX handler
        new AppointmentAjaxHandler($this->logger);

        if (is_admin()) {
            new AdminController();
        } else {
            new FrontendController($this->logger, $this->twig);
        }

        new ShortcodeManager($this->logger, $this->twig);
    }

    /**
     * Activate the plugin
     */
    public static function activate()
    {
        // Actions d'activation
        // Flush rewrite rules to register custom post type
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        // Actions de désactivation
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
    }
}
