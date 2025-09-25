<?php

namespace App\Application;

use App\Domain\Exceptions\AppException;
use App\Infrastructure\Logging\LoggerInterface;
use ErrorException;
use Throwable;

class ExceptionHandler {
    private LoggerInterface $logger;
    private bool $showErrors;

    public function __construct(LoggerInterface $logger, bool $showErrors = false) {
        $this->logger = $logger;
        $this->showErrors = $showErrors;
    }

    public function handle(Throwable $exception): void {
        $this->logger->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $httpCode = $exception instanceof AppException ?
            $exception->getHttpCode() : 500;

        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        if ($exception instanceof AppException) {
            $response = $exception->toArray();
        } else {
            $response = [
                'error' => true,
                'message' => $this->showErrors ? $exception->getMessage() : 'Internal server error',
                'code' => 500,
                'timestamp' => date('c')
            ];

            if ($this->showErrors) {
                $response['file'] = $exception->getFile();
                $response['line'] = $exception->getLine();
            }
        }

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function register(): void {
        set_exception_handler([$this, 'handle']);
        set_error_handler([$this, 'handleError']);
    }

    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        $this->handle($exception);
        return true;
    }
}
