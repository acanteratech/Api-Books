<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Book;
use App\Domain\Exceptions\NotFoundException;

interface BookRepositoryInterface
{
    /**
     * Encuentra un libro por ID
     * @throws NotFoundException Si el libro no existe
     */
    public function findById(int $id): Book;

    /**
     * Busca libros por título o autor
     * @return Book[]
     */
    public function search(string $query): array;

    /**
     * Obtiene todos los libros
     * @return Book[]
     */
    public function findAll(): array;

    /**
     * Guarda un libro (crea o actualiza)
     */
    public function save(Book $book): void;

    /**
     * Elimina un libro por ID
     * @throws NotFoundException Si el libro no existe
     */
    public function delete(int $id): void;

    /**
     * Verifica si existe un libro por ISBN
     */
    public function existsByIsbn(string $isbn): bool;
}
