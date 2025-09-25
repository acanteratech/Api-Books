<?php

// Configuración UTF-8
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../autoload.php';

use App\Infrastructure\Logging\FileLogger;
use App\Application\ExceptionHandler;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Persistence\MySQLBookRepository;
use App\Infrastructure\External\OpenLibraryService;
use App\Application\Controllers\BookController;
use App\Application\Requests\HttpRequest;

// Cargar configuración
$config = loadConfig('app');
$dbConfig = loadConfig('database');

// Inicializar logger
$logger = new FileLogger($config['log_file']);

// Configurar manejador de excepciones
$exceptionHandler = new ExceptionHandler($logger, $config['display_errors']);
$exceptionHandler->register();

try {
    // Inicializar servicios
    $dbConnection = DatabaseConnection::getInstance($dbConfig, $logger);
    $openLibraryService = new OpenLibraryService($logger);
    $bookRepository = new MySQLBookRepository($dbConnection, $openLibraryService, $logger, true);
    $bookController = new BookController($bookRepository);

    $method = HttpRequest::getMethod();
    $path = HttpRequest::getPath();

    $path = rtrim($path, '/') ?: '/';

    // Router básico
    $logger->info("Request recibida", [
        'method' => $method,
        'path' => $path,
        'full_uri' => $_SERVER['REQUEST_URI'] ?? ''
    ]);

    // Home/health check
    if ($path === '/' && $method === 'GET') {
        echo json_encode([
            'status' => 'OK',
            'message' => 'Book Manager API está funcionando',
            'base_url' => 'http://apibooks/',
            'endpoints' => [
                'GET /books' => 'Listar todos los libros',
                'POST /books' => 'Crear nuevo libro',
                'GET /books/{id}' => 'Obtener libro por ID',
                'PUT /books/{id}' => 'Actualizar libro',
                'DELETE /books/{id}' => 'Eliminar libro',
                'GET /books/search?q=query' => 'Buscar libros'
            ],
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Routes API
    switch (true) {
        case $path === '/books' && $method === 'GET':
            $bookController->index();
            break;

        case $path === '/books' && $method === 'POST':
            $bookController->store();
            break;

        case preg_match('#^/books/(\d+)$#', $path, $matches) && $method === 'GET':
            $bookController->show((int)$matches[1]);
            break;

        case preg_match('#^/books/(\d+)$#', $path, $matches) && $method === 'PUT':
            $bookController->update((int)$matches[1]);
            break;

        case preg_match('#^/books/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $bookController->destroy((int)$matches[1]);
            break;

        case $path === '/books/search' && $method === 'GET':
            $bookController->search();
            break;

        default:
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => true,
                'message' => 'Endpoint no encontrado',
                'path' => $path,
                'method' => $method,
                'available_endpoints' => [
                    'GET /' => 'Health check',
                    'GET /books' => 'Listar libros',
                    'POST /books' => 'Crear libro',
                    'GET /books/{id}' => 'Obtener libro',
                    'PUT /books/{id}' => 'Actualizar libro',
                    'DELETE /books/{id}' => 'Eliminar libro',
                    'GET /books/search?q=query' => 'Buscar libros'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Throwable $e) {
    $exceptionHandler->handle($e);
}
