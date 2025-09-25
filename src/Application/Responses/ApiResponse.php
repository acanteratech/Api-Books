<?php

namespace App\Application\Responses;

class ApiResponse {
    public static function success($data = null, string $message = null, int $httpCode = 200): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public static function error(string $message, int $httpCode = 500, array $details = []): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('c')
        ];

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public static function notFound(string $resource = 'Recurso'): void {
        self::error("$resource no encontrado", 404);
    }

    public static function validationError(array $errors): void {
        self::error("Error de validación", 400, $errors);
    }
}
