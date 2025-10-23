<?php
/**
 * Plugin Name: GDH Rendez-vous 
 * Description: Plugin WordPress pour la prise de rendez-vous en ligne via un formulaire multi-étapes dans une popup.
 * Version: 1.0.1
 * Author: R&D Team - weshore
 * Developpers : Abdelkarim & Ilyas
 */


// Sécurité: Empêche l'accès direct
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

define('GDHRDV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GDHRDV_PLUGIN_URL', plugin_dir_url(__FILE__));
define("GDHRDV_VERSION",'1.0.1');

// Sécurité: Valide les chemins avant inclusion
$vendor_autoload = rtrim(GDHRDV_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$core_autoloader = rtrim(GDHRDV_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Autoloader.php';

if (file_exists($vendor_autoload) && is_readable($vendor_autoload)) {
    require_once $vendor_autoload;
}

if (file_exists($core_autoloader) && is_readable($core_autoloader)) {
    require_once $core_autoloader;
} else {
    wp_die('Plugin autoloader not found.');
}
\GDHRDV\Core\Autoloader::register();

use GDHRDV\Core\Plugin;

// Initialisation du plugin
add_action('init', function() {
    Plugin::getInstance()->init();
}, 5);

// Activation/Désactivation
register_activation_hook(__FILE__, [Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);
