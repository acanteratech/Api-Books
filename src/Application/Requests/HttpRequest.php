<?php

namespace App\Application\Requests;

class HttpRequest {
    public static function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function getPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        return rtrim($path, '/');
    }

    public static function getBody(): array {
        $method = self::getMethod();

        if ($method === 'POST' || $method === 'PUT') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                return json_decode($input, true) ?? [];
            }

            return $_POST;
        }

        return [];
    }

    public static function getQueryParams(): array {
        return $_GET;
    }

    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }

        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
