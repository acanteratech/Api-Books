<?php

namespace App\Infrastructure\Logging;

class FileLogger implements LoggerInterface {
    private string $logFile;

    public function __construct(string $logFile = 'app.log') {
        $this->logFile = $logFile;

        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr\n";

        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void {
        $this->log('info', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->log('error', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->log('debug', $message, $context);
    }
}
