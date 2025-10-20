<?php
namespace GDH\Services;

class Logger
{
    private $logFile;

    public function __construct()
    {
        $this->logFile = GDH_PLUGIN_PATH . 'logs/gdh.log';
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
        $timestamp = date('Y-m-d H:i:s');
        $logEntry  = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
