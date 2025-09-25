<?php

namespace App\Domain\Exceptions;

class NotFoundException extends AppException {
    public function __construct(string $resource = "Resource", string $message = null) {
        $message = $message ?: "$resource not found";
        parent::__construct($message, 404);
    }
}
