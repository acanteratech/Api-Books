<?php

namespace App\Domain\Exceptions;

use Exception;
use Throwable;

class AppException extends Exception {
    protected int $httpCode;
    protected array $details;

    public function __construct(
        string $message = "Application error",
        int $httpCode = 500,
        array $details = [],
        Throwable $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
        $this->details = $details;
    }

    public function getHttpCode(): int {
        return $this->httpCode;
    }

    public function getDetails(): array {
        return $this->details;
    }

    public function toArray(): array {
        return [
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getHttpCode(),
            'details' => $this->getDetails(),
            'timestamp' => date('c')
        ];
    }
}
