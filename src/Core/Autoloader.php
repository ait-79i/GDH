<?php
namespace GDHRDV\Core;

class Autoloader
{
    /**
     * Préfixe d'espace de noms PSR-4
     */
    protected static $prefix = 'GDHRDV\\';

    /**
     * Répertoire de base pour le préfixe d'espace de noms
     */
    protected static $baseDir;

    public static function register(): void
    {
        // Détermine le répertoire de base à partir de la constante du plugin si disponible
        if (defined('GDHRDV_PLUGIN_PATH')) {
            self::$baseDir = rtrim(GDHRDV_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        } else {
            // Solution de repli : déduire depuis ce fichier
            self::$baseDir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR;
        }

        spl_autoload_register([self::class, 'autoload']);
    }

    protected static function autoload(string $class): void
    {
        // Ne gérer que notre espace de noms
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return;
        }

        // Récupère le nom de classe relatif
        $relativeClass = substr($class, $len);
        
        // Sécurité : Valide que le nom de classe ne contient que des caractères autorisés
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff\\]*$/', $relativeClass)) {
            return;
        }
        
        // Sécurité : Empêche les attaques de traversal de chemins
        if (strpos($relativeClass, '..') !== false || strpos($relativeClass, '/') !== false) {
            return;
        }

        // Remplace les séparateurs d'espaces de noms par des séparateurs de répertoires et ajoute .php
        $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        
        // Sécurité : S'assure que le fichier se trouve bien dans le répertoire de base
        $realFile = realpath($file);
        $realBase = realpath(self::$baseDir);
        if ($realFile && $realBase && strpos($realFile, $realBase) === 0 && is_file($realFile)) {
            require_once $realFile;
        }
    }
}
