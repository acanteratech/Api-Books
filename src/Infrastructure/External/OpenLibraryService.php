<?php

namespace App\Infrastructure\External;

use App\Domain\Exceptions\ExternalServiceException;
use App\Infrastructure\Logging\LoggerInterface;
use Exception;

class OpenLibraryService {
    private LoggerInterface $logger;
    private string $baseUrl = 'https://openlibrary.org';
    private int $timeout = 10;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function getBookDataByIsbn(string $isbn): array {
        try {
            $url = $this->buildApiUrl($isbn);
            $this->logger->info("Consultando Open Library API para ISBN: $isbn");

            $response = $this->makeHttpRequest($url);
            $data = $this->parseResponse($response, $isbn);

            $this->logger->info("Datos obtenidos para ISBN: $isbn");
            return $data;
        } catch (ExternalServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error("Error inesperado", [
                'isbn' => $isbn,
                'error' => $e->getMessage()
            ]);

            throw new ExternalServiceException('Open Library', "Error: " . $e->getMessage(), $e);
        }
    }

    private function buildApiUrl(string $isbn): string {
        return $this->baseUrl . '/api/books?' . http_build_query([
            'bibkeys' => 'ISBN:' . $isbn,
            'format' => 'json',
            'jscmd' => 'data'
        ]);
    }

    private function makeHttpRequest(string $url): string {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'header' => "User-Agent: BookManager/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            throw new ExternalServiceException(
                'Open Library',
                "No se pudo conectar a la API: " . ($error['message'] ?? 'Error desconocido')
            );
        }

        return $response;
    }

    private function parseResponse(string $response, string $isbn): array {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ExternalServiceException(
                'Open Library',
                "Respuesta JSON inválida"
            );
        }

        $bookKey = 'ISBN:' . $isbn;

        if (!isset($data[$bookKey])) {
            throw new ExternalServiceException(
                'Open Library',
                "No se encontraron datos para el ISBN: $isbn"
            );
        }

        $bookData = $data[$bookKey];

        return [
            'description' => $this->extractDescription($bookData),
            'cover_url' => $this->extractCoverUrl($bookData)
        ];
    }

    private function extractDescription(array $apiData): ?string {
        // Crear descripción básica
        $parts = [];
        if (isset($apiData['number_of_pages'])) {
            $parts[] = $apiData['number_of_pages'] . ' páginas';
        }
        if (isset($apiData['publishers'][0]['name'])) {
            $parts[] = 'Editorial: ' . $apiData['publishers'][0]['name'];
        }

        if (!empty($parts)) {
            return implode(' - ', $parts);
        }

        return null;
    }

    private function extractCoverUrl(array $apiData): ?string {
        if (isset($apiData['cover'])) {
            // Preferir la imagen más grande disponible
            if (isset($apiData['cover']['large'])) {
                return $apiData['cover']['large'];
            } elseif (isset($apiData['cover']['medium'])) {
                return $apiData['cover']['medium'];
            } elseif (isset($apiData['cover']['small'])) {
                return $apiData['cover']['small'];
            }
        }

        return null;
    }
}
