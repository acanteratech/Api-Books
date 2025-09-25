<?php

namespace App\Infrastructure\Persistence;

use PDO;
use PDOException;
use App\Domain\Exceptions\AppException;
use App\Infrastructure\Logging\LoggerInterface;

class DatabaseConnection {
    private static ?DatabaseConnection $instance = null;
    private PDO $connection;
    private LoggerInterface $logger;

    private function __construct(array $config, LoggerInterface $logger) {
        $this->logger = $logger;
        $this->connect($config);
    }

    public static function getInstance(array $config, LoggerInterface $logger): self {
        if (self::$instance === null) {
            self::$instance = new self($config, $logger);
        }
        return self::$instance;
    }

    private function connect(array $config): void {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );

            $this->logger->info('Conexión a base de datos establecida', [
                'host' => $config['host'],
                'database' => $config['database']
            ]);
        } catch (PDOException $e) {
            $this->logger->error('Error de conexión a base de datos', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw new AppException(
                'No se pudo conectar a la base de datos',
                503, // Service Unavailable
                [],
                $e
            );
        }
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SELECT y retorna los resultados
     * @return array
     */
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            $result = $stmt->fetchAll();
            $this->logger->debug('Consulta SELECT ejecutada', [
                'sql' => $sql,
                'params' => $params,
                'row_count' => count($result)
            ]);

            return $result;
        } catch (PDOException $e) {
            $this->logger->error('Error en consulta SELECT', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            throw new AppException(
                'Error en la consulta de base de datos',
                500,
                ['sql' => $this->obfuscateSql($sql)],
                $e
            );
        }
    }

    /**
     * Ejecuta una consulta INSERT/UPDATE/DELETE
     * @return int Número de filas afectadas
     */
    public function execute(string $sql, array $params = []): int {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            $rowCount = $stmt->rowCount();
            $this->logger->debug('Consulta ejecutada', [
                'sql' => $sql,
                'params' => $params,
                'rows_affected' => $rowCount
            ]);

            return $rowCount;
        } catch (PDOException $e) {
            $this->logger->error('Error en consulta de escritura', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            throw new AppException(
                'Error en la operación de base de datos',
                500,
                ['sql' => $this->obfuscateSql($sql)],
                $e
            );
        }
    }

    /**
     * Obtiene el último ID insertado
     */
    public function getLastInsertId(): int {
        return (int)$this->connection->lastInsertId();
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): void {
        $this->connection->beginTransaction();
        $this->logger->debug('Transacción iniciada');
    }

    /**
     * Confirma una transacción
     */
    public function commit(): void {
        $this->connection->commit();
        $this->logger->debug('Transacción confirmada');
    }

    /**
     * Revierte una transacción
     */
    public function rollback(): void {
        $this->connection->rollBack();
        $this->logger->debug('Transacción revertida');
    }

    /**
     * Oculta valores sensibles en el SQL para logging
     */
    private function obfuscateSql(string $sql): string {
        return preg_replace('/\'(.*?)\'/', "'***'", $sql);
    }
}
