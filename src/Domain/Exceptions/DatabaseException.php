<?php

namespace App\Domain\Exceptions;

use Throwable;

class DatabaseException extends AppException {
    public function __construct(string $message = "Database error", Throwable $previous = null) {
        parent::__construct($message, 500, [], $previous);
    }
}
