<?php
namespace GDH\Services;

class Logger
{
    private $logFile;

    public function __construct()
    {
        // Security: Ensure log file is in safe location
        $logDir = rtrim(GDH_PLUGIN_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        $this->logFile = $logDir . DIRECTORY_SEPARATOR . 'gdh.log';
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
        $this->log('INFO', $message);
    }

    public function error($message)
    {
        $this->log('ERROR', $message);
    }

    public function warning($message)
    {
        $this->log('WARNING', $message);
    }

    private function log($level, $message)
    {
        // Security: Sanitize inputs
        $level = preg_replace('/[^A-Z]/', '', strtoupper($level));
        $message = sanitize_text_field($message);
        
        // Security: Validate log file path
        $realLogFile = realpath(dirname($this->logFile));
        $realPluginPath = realpath(GDH_PLUGIN_PATH);
        
        if (!$realLogFile || !$realPluginPath || strpos($realLogFile, $realPluginPath) !== 0) {
            return; // Prevent writing outside plugin directory
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] [%s] %s%s", $timestamp, $level, $message, PHP_EOL);
        
        // Security: Use WordPress filesystem API when available
        if (function_exists('WP_Filesystem')) {
            global $wp_filesystem;
            if (WP_Filesystem()) {
                $existing = $wp_filesystem->exists($this->logFile) ? $wp_filesystem->get_contents($this->logFile) : '';
                $wp_filesystem->put_contents($this->logFile, $existing . $logEntry, FS_CHMOD_FILE);
                return;
            }
        }
        
        // Fallback to file_put_contents with security checks
        if (is_writable(dirname($this->logFile))) {
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
}
