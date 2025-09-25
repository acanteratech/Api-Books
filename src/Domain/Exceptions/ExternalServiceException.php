<?php

namespace App\Domain\Exceptions;

use Throwable;

class ExternalServiceException extends AppException {
    public function __construct(
        string $serviceName,
        string $message = "External service error",
        Throwable $previous = null
    ) {
        $message = "$serviceName: $message";
        parent::__construct($message, 503, [], $previous);
    }
}
