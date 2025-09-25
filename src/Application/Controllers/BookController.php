<?php

namespace App\Application\Controllers;

use App\Domain\Entities\Book;
use App\Domain\Exceptions\NotFoundException;
use App\Domain\Exceptions\ValidationException;
use App\Infrastructure\Persistence\MySQLBookRepository;
use App\Application\Requests\HttpRequest;
use App\Application\Responses\ApiResponse;
use Exception;

class BookController {
    private MySQLBookRepository $bookRepository;

    public function __construct(MySQLBookRepository $bookRepository) {
        $this->bookRepository = $bookRepository;
    }

    /**
     * GET /books - Listar todos los libros
     */
    public function index(): void {
        try {
            $books = $this->bookRepository->findAll();

            // Convertir objetos Book a arrays para la respuesta
            $booksArray = array_map(function(Book $book) {
                return $this->bookToArray($book);
            }, $books);

            ApiResponse::success($booksArray, 'Libros obtenidos correctamente');
        } catch (Exception $e) {
            ApiResponse::error('Error al obtener los libros: ' . $e->getMessage());
        }
    }

    /**
     * GET /books/{id} - Obtener un libro por ID
     */
    public function show(int $id): void {
        try {
            $book = $this->bookRepository->findById($id);
            ApiResponse::success($this->bookToArray($book), 'Libro obtenido correctamente');
        } catch (NotFoundException $e) {
            ApiResponse::notFound('Libro');
        } catch (Exception $e) {
            ApiResponse::error('Error al obtener el libro: ' . $e->getMessage());
        }
    }

    /**
     * POST /books - Crear un nuevo libro
     */
    public function store(): void {
        try {
            $data = HttpRequest::getBody();
            $data = HttpRequest::sanitizeInput($data);

            $errors = $this->validateBookData($data);
            if (!empty($errors)) {
                ApiResponse::validationError($errors);
                return;
            }

            $book = Book::create(
                $data['title'],
                $data['author'],
                $data['isbn'],
                (int)$data['publication_year']
            );

            $this->bookRepository->save($book);

            ApiResponse::success(
                $this->bookToArray($book),
                'Libro creado correctamente',
                201
            );
        } catch (ValidationException $e) {
            ApiResponse::validationError($e->getDetails());
        } catch (Exception $e) {
            ApiResponse::error('Error al crear el libro: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /books/{id} - Actualizar un libro existente
     */
    public function update(int $id): void {
        try {
            // Verificar que el libro existe
            $existingBook = $this->bookRepository->findById($id);

            $data = HttpRequest::getBody();
            $data = HttpRequest::sanitizeInput($data);

            // Validar campos requeridos
            $errors = $this->validateBookData($data, false); // false para actualización (campos no requeridos)
            if (!empty($errors)) {
                ApiResponse::validationError($errors);
                return;
            }

            // Actualizar campos permitidos
            if (isset($data['title'])) {
                $existingBook->title = $data['title'];
            }
            if (isset($data['author'])) {
                $existingBook->author = $data['author'];
            }
            if (isset($data['isbn'])) {
                $existingBook->isbn = $data['isbn'];
            }
            if (isset($data['publication_year'])) {
                $existingBook->publicationYear = (int)$data['publication_year'];
            }
            if (isset($data['description'])) {
                $existingBook->description = $data['description'];
            }
            if (isset($data['cover_url'])) {
                $existingBook->coverUrl = $data['cover_url'];
            }

            $this->bookRepository->save($existingBook);

            ApiResponse::success($this->bookToArray($existingBook), 'Libro actualizado correctamente');
        } catch (NotFoundException $e) {
            ApiResponse::notFound('Libro');
        } catch (ValidationException $e) {
            ApiResponse::validationError($e->getDetails());
        } catch (Exception $e) {
            ApiResponse::error('Error al actualizar el libro: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /books/{id} - Eliminar un libro (soft delete)
     */
    public function destroy(int $id): void {
        try {
            $this->bookRepository->delete($id);
            ApiResponse::success(null, 'Libro eliminado correctamente');
        } catch (NotFoundException $e) {
            ApiResponse::notFound('Libro');
        } catch (Exception $e) {
            ApiResponse::error('Error al eliminar el libro: ' . $e->getMessage());
        }
    }

    /**
     * GET /books/search?q=query - Buscar libros
     */
    public function search(): void {
        try {
            $queryParams = HttpRequest::getQueryParams();
            $searchQuery = $queryParams['q'] ?? '';

            if (empty($searchQuery)) {
                ApiResponse::error('Parámetro de búsqueda (q) requerido', 400);
                return;
            }

            $books = $this->bookRepository->search($searchQuery);

            $booksArray = array_map(function(Book $book) {
                return $this->bookToArray($book);
            }, $books);

            ApiResponse::success($booksArray, 'Búsqueda completada');
        } catch (Exception $e) {
            ApiResponse::error('Error en la búsqueda: ' . $e->getMessage());
        }
    }

    /**
     * Convierte un objeto Book a array para la respuesta
     */
    private function bookToArray(Book $book): array {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'publication_year' => $book->publicationYear,
            'description' => $book->description,
            'cover_url' => $book->coverUrl,
            'status' => $book->status,
            'created_at' => $book->createdAt->format('c'),
            'updated_at' => $book->updatedAt ? $book->updatedAt->format('c') : null,
            'deleted_at' => $book->deletedAt ? $book->deletedAt->format('c') : null
        ];
    }

    /**
     * Valida los datos del libro
     */
    private function validateBookData(array $data, bool $isCreate = true): array {
        $errors = [];

        if ($isCreate) {
            if (empty($data['title'])) {
                $errors['title'] = 'El título es requerido';
            }
            if (empty($data['author'])) {
                $errors['author'] = 'El autor es requerido';
            }
            if (empty($data['isbn'])) {
                $errors['isbn'] = 'El ISBN es requerido';
            }
            if (empty($data['publication_year'])) {
                $errors['publication_year'] = 'El año de publicación es requerido';
            } elseif (!is_numeric($data['publication_year'])) {
                $errors['publication_year'] = 'El año de publicación debe ser un número';
            }
        } else {
            // Para actualización, validar solo los campos presentes
            if (isset($data['title']) && empty($data['title'])) {
                $errors['title'] = 'El título no puede estar vacío';
            }
            if (isset($data['author']) && empty($data['author'])) {
                $errors['author'] = 'El autor no puede estar vacío';
            }
            if (isset($data['isbn']) && empty($data['isbn'])) {
                $errors['isbn'] = 'El ISBN no puede estar vacío';
            }
            if (isset($data['publication_year']) && !is_numeric($data['publication_year'])) {
                $errors['publication_year'] = 'El año de publicación debe ser un número';
            }
        }

        return $errors;
    }
}
