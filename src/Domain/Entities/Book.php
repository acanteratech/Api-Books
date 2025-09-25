<?php

namespace App\Domain\Entities;

use DateTime;
use InvalidArgumentException;

class Book {
    public ?int $id;
    public string $title;
    public string $author;
    public string $isbn;
    public int $publicationYear;
    public DateTime $createdAt;
    public int $status;
    public ?string $description;
    public ?string $coverUrl;
    public ?DateTime $updatedAt;
    public ?DateTime $deletedAt;

    public function __construct(
        ?int $id,
        string $title,
        string $author,
        string $isbn,
        int $publicationYear,
        DateTime $createdAt,
        int $status = 1,
        ?string $description = null,
        ?string $coverUrl = null,
        ?DateTime $updatedAt = null,
        ?DateTime $deletedAt = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->isbn = $isbn;
        $this->publicationYear = $publicationYear;
        $this->createdAt = $createdAt;
        $this->status = $status;
        $this->description = $description;
        $this->coverUrl = $coverUrl;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

    /**
     * Crea libros nuevos
     */
    public static function create(
        string $title,
        string $author,
        string $isbn,
        int $publicationYear,
        int $status = 1
    ): self {
        return new self(
            null,  // ID se genera en la BD
            $title,
            $author,
            $isbn,
            $publicationYear,
            new DateTime(),
            $status
        );
    }

    /**
     * Reconstruye la entidad desde BD
     */
    public static function fromDatabase(
        int $id,
        string $title,
        string $author,
        string $isbn,
        int $publicationYear,
        string $createdAt,
        int $status,
        ?string $description,
        ?string $coverUrl,
        ?string $updatedAt,
        ?string $deletedAt
    ): self {
        return new self(
            $id,
            $title,
            $author,
            $isbn,
            $publicationYear,
            new DateTime($createdAt),
            $status,
            $description,
            $coverUrl,
            $updatedAt ? new DateTime($updatedAt) : null,
            $deletedAt ? new DateTime($deletedAt) : null
        );
    }

    /**
     * Valida los datos del libro
     * @throws InvalidArgumentException Si la validación falla
     */
    public function validate(): void {
        if (empty($this->title)) {
            throw new InvalidArgumentException("El título es requerido");
        }

        if (empty($this->author)) {
            throw new InvalidArgumentException("El autor es requerido");
        }

        if (empty($this->isbn) || !$this->isValidIsbn($this->isbn)) {
            throw new InvalidArgumentException("ISBN inválido");
        }

        if ($this->publicationYear < 1000 || $this->publicationYear > (int)date('Y') + 1) {
            throw new InvalidArgumentException("Año de publicación inválido");
        }

        if ($this->status < 0 || $this->status > 1) {
            throw new InvalidArgumentException("Estado debe ser 0 o 1");
        }
    }

    private function isValidIsbn(string $isbn): bool {
        // Limpiar ISBN (eliminar guiones y espacios)
        $isbn = preg_replace('/[^0-9X]/i', '', $isbn);
        return strlen($isbn) <= 20;
    }

    /**
     * Marca el libro como eliminado
     */
    public function markAsDeleted(): void {
        $this->status = 0;
        $this->deletedAt = new DateTime();
    }

    /**
     * Actualiza timestamp de modificación
     */
    public function touch(): void {
        $this->updatedAt = new DateTime();
    }
}
