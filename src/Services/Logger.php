<?php
namespace GDHRDV\Services;

class Logger
{
    private $logFile;
    private $maxFileSize = 5242880; // 5MB
    private $enableLogging;

    public function __construct()
    {
        // Active le logging uniquement en mode debug ou pour les erreurs
        $this->enableLogging = defined('WP_DEBUG') && WP_DEBUG;
        
        $logDir = rtrim(GDHRDV_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        $this->logFile = $logDir . DIRECTORY_SEPARATOR . 'gdhrdv.log';
        $this->ensureLogDirectory();
    }

    private function ensureLogDirectory()
    {
        $logDir = dirname($this->logFile);
        if (! file_exists($logDir)) {
            wp_mkdir_p($logDir);
        }
    }

    public function info($message)
    {
        // Log les messages info uniquement en mode debug
        if ($this->enableLogging) {
            $this->log('INFO', $message);
        }
    }

    public function error($message)
    {
        // Log toujours les erreurs
        $this->log('ERROR', $message);
    }

    public function warning($message)
    {
        // Log les avertissements uniquement en mode debug
        if ($this->enableLogging) {
            $this->log('WARNING', $message);
        }
    }

    private function log($level, $message)
    {
        // Sécurité: Nettoie les entrées
        $level = preg_replace('/[^A-Z]/', '', strtoupper($level));
        $message = sanitize_text_field($message);
        
        // Sécurité: Valide le chemin du fichier de log
        $realLogFile = realpath(dirname($this->logFile));
        $realPluginPath = realpath(GDHRDV_PLUGIN_PATH);
        
        if (!$realLogFile || !$realPluginPath || strpos($realLogFile, $realPluginPath) !== 0) {
            return;
        }
        
        // Rotation du log s'il est trop volumineux
        $this->rotateLogIfNeeded();
        
        $timestamp = current_time('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] [%s] %s%s", $timestamp, $level, $message, PHP_EOL);
        
        // Fallback vers file_put_contents avec vérifications de sécurité
        if (is_writable(dirname($this->logFile))) {
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    private function rotateLogIfNeeded()
    {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) > $this->maxFileSize) {
            $backupFile = $this->logFile . '.old';
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
            rename($this->logFile, $backupFile);
        }
    }
}
