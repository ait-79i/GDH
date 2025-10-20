<?php
namespace GDH\Core;

class Autoloader
{
    /**
     * PSR-4 base namespace prefix
     */
    protected static $prefix = 'GDH\\';

    /**
     * Base directory for the namespace prefix
     */
    protected static $baseDir;

    public static function register(): void
    {
        // Determine base directory from plugin path constant if available
        if (defined('GDH_PLUGIN_PATH')) {
            self::$baseDir = rtrim(GDH_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        } else {
            // Fallback: derive from this file
            self::$baseDir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR;
        }

        spl_autoload_register([self::class, 'autoload']);
    }

    protected static function autoload(string $class): void
    {
        // Only handle our namespace
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return;
        }

        // Get the relative class name
        $relativeClass = substr($class, $len);

        // Replace namespace separators with directory separators, append .php
        $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
}
