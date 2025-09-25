<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Book;
use App\Domain\Repositories\BookRepositoryInterface;
use App\Domain\Exceptions\NotFoundException;
use App\Infrastructure\External\OpenLibraryService;
use App\Infrastructure\Logging\LoggerInterface;
use Exception;

class MySQLBookRepository implements BookRepositoryInterface {
    private DatabaseConnection $db;
    private OpenLibraryService $openLibraryService;
    private LoggerInterface $logger;
    private bool $autoFetchApiData;

    public function __construct(
        DatabaseConnection $db,
        OpenLibraryService $openLibraryService,
        LoggerInterface $logger,
        bool $autoFetchApiData = true
    ) {
        $this->db = $db;
        $this->openLibraryService = $openLibraryService;
        $this->logger = $logger;
        $this->autoFetchApiData = $autoFetchApiData;
    }

    public function findById(int $id): Book {
        $result = $this->db->query(
            "SELECT * FROM books WHERE id = ? AND status = 1",
            [$id]
        );

        if (empty($result)) {
            throw new NotFoundException("Libro con ID $id");
        }

        return $this->mapToBook($result[0]);
    }

    public function search(string $query): array {
        $searchTerm = "%$query%";
        $result = $this->db->query(
            "SELECT * FROM books
             WHERE (title LIKE ? OR author LIKE ? OR publication_year LIKE ?)
                AND status = 1
             ORDER BY title ASC",
            [$searchTerm, $searchTerm, $searchTerm]
        );

        return array_map([$this, 'mapToBook'], $result);
    }

    public function findAll(): array {
        $result = $this->db->query(
            "SELECT * FROM books WHERE status = 1 ORDER BY created_at DESC"
        );

        return array_map([$this, 'mapToBook'], $result);
    }

    public function save(Book $book): void {
        $book->validate();

        if ($this->autoFetchApiData && $this->shouldFetchApiData($book)) {
            $this->enrichBookFromOpenLibrary($book);
        }

        if ($book->id === null) {
            // INSERT
            $this->db->execute(
                "INSERT INTO books
                 (title, author, isbn, publication_year, created_at, status, description, cover_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $book->title,
                    $book->author,
                    $book->isbn,
                    $book->publicationYear,
                    $book->createdAt->format('Y-m-d H:i:s'),
                    $book->status,
                    $book->description,
                    $book->coverUrl
                ]
            );

            // Obtener el ID generado
            $book->id = $this->db->getLastInsertId();
        } else {
            // UPDATE
            $book->touch();

            $this->db->execute(
                "UPDATE books SET
                 title = ?, author = ?, isbn = ?, publication_year = ?,
                 description = ?, cover_url = ?, updated_at = ?
                 WHERE id = ?",
                [
                    $book->title,
                    $book->author,
                    $book->isbn,
                    $book->publicationYear,
                    $book->description,
                    $book->coverUrl,
                    $book->updatedAt->format('Y-m-d H:i:s'),
                    $book->id
                ]
            );
        }
    }

    public function delete(int $id): void {
        // Verificar que existe primero
        $book = $this->findById($id);

        $book->markAsDeleted();

        $this->db->execute(
            "UPDATE books SET deleted_at = ?, status = ? WHERE id = ?",
            [
                $book->deletedAt->format('Y-m-d H:i:s'),
                $book->status,
                $id
            ]
        );
    }

    public function existsByIsbn(string $isbn): bool {
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM books WHERE isbn = ?",
            [$isbn]
        );

        return $result[0]['count'] > 0;
    }

    /**
     * Decide si debemos consultar la API para este libro
     */
    private function shouldFetchApiData(Book $book): bool {
        // Solo consultar la API si no tenemos descripción NI portada
        return empty($book->description) && empty($book->coverUrl);
    }

    /**
     * Obtiene y actualiza los datos del libro desde Open Library
     */
    private function enrichBookFromOpenLibrary(Book $book): void {
        try {
            $apiData = $this->openLibraryService->getBookDataByIsbn($book->isbn);

            if (!empty($apiData['description'])) {
                $book->description = $apiData['description'];
            }

            if (!empty($apiData['cover_url'])) {
                $book->coverUrl = $apiData['cover_url'];
            }

            $this->logger->info("Libro enriquecido con datos de Open Library", [
                'isbn' => $book->isbn,
                'description_added' => !empty($apiData['description']),
                'cover_added' => !empty($apiData['cover_url'])
            ]);
        } catch (Exception $e) {
            $this->logger->error(
                "No se pudieron obtener datos de Open Library para ISBN: {$book->isbn}",
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Convierte un array de base de datos a objeto Book
     */
    private function mapToBook(array $data): Book {
        return Book::fromDatabase(
            (int)$data['id'],
            $data['title'],
            $data['author'],
            $data['isbn'],
            (int)$data['publication_year'],
            $data['created_at'],
            (int)$data['status'],
            $data['description'],
            $data['cover_url'],
            $data['updated_at'],
            $data['deleted_at']
        );
    }
}
