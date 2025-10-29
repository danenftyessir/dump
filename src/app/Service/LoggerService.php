<?php

namespace Service;

class LoggerService
{
    private $logFile;

    // Ctor
    public function __construct() {
        $this->logFile = dirname(__DIR__) . '/../logs/app.log';
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    // Log Message
    public function logError($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }

    public function logInfo($message, $context = []) {
        $this->log('INFO', $message, $context);
    }

    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] [$level]: $message $contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $this->logFile);
    }
}