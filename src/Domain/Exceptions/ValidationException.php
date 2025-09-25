<?php

namespace App\Domain\Exceptions;

class ValidationException extends AppException {
    public function __construct(array $validationErrors = [], string $message = "Validation failed") {
        parent::__construct($message, 400, $validationErrors);
    }
}
