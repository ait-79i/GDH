<?php
/**
 * Plugin Name: GDH Rendez-vous 
 * Description: Plugin WordPress pour la prise de rendez-vous en ligne via un formulaire multi-étapes dans une popup.
 * Version: 1.0.0
 * Author: Abdelkarim AIT HQI
 */


if (!defined('ABSPATH')) {
    exit;
}

define('GDH_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GDH_PLUGIN_URL', plugin_dir_url(__FILE__));
define("GDH_VERSION",'1.0.0');

require_once GDH_PLUGIN_PATH . 'vendor/autoload.php';
require_once GDH_PLUGIN_PATH . 'src/Core/Autoloader.php';
\GDH\Core\Autoloader::register();

use GDH\Core\Plugin;

// Initialisation du plugin
add_action('plugins_loaded', function() {
    Plugin::getInstance()->init();
});

// Activation/Désactivation
register_activation_hook(__FILE__, [Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);