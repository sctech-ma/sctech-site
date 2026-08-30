<?php

declare(strict_types=1);

namespace SCTech\Core;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

final class Database
{
    private ?PDO $connection = null;
    private int $transactionDepth = 0;

    /** @var array<string, mixed> */
    private array $configuration = [];

    /** @param PDO|Config|array<string, mixed> $configuration */
    public function __construct(PDO|Config|array $configuration)
    {
        if ($configuration instanceof PDO) {
            $this->connection = $configuration;
            $this->configurePdo($this->connection);
            return;
        }

        $this->configuration = $configuration instanceof Config
            ? (array) $configuration->get('database', [])
            : $configuration;
    }

    public function pdo(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $driver = strtolower((string) ($this->configuration['driver'] ?? 'mysql'));
        $username = (string) ($this->configuration['username'] ?? '');
        $password = (string) ($this->configuration['password'] ?? '');
        $options = (array) ($this->configuration['options'] ?? []);

        if (isset($this->configuration['dsn'])) {
            $dsn = (string) $this->configuration['dsn'];
        } elseif ($driver === 'sqlite') {
            $dsn = 'sqlite:' . (string) ($this->configuration['database'] ?? ':memory:');
        } elseif ($driver === 'mysql') {
            $host = (string) ($this->configuration['host'] ?? '127.0.0.1');
            $port = (int) ($this->configuration['port'] ?? 3306);
            $database = (string) ($this->configuration['database'] ?? '');
            $charset = (string) ($this->configuration['charset'] ?? 'utf8mb4');
            if ($database === '') {
                throw new InvalidArgumentException('Database name is required for MySQL connections.');
            }
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
        } else {
            throw new InvalidArgumentException(sprintf('Unsupported database driver "%s".', $driver));
        }

        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        $this->connection = new PDO($dsn, $username, $password, $options + $defaults);
        $this->configurePdo($this->connection);

        return $this->connection;
    }

    /** @param array<string|int, mixed> $parameters */
    public function statement(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        foreach ($parameters as $key => $value) {
            $parameter = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $statement->bindValue($parameter, $value, $type);
        }
        $statement->execute();

        return $statement;
    }

    /** @param array<string|int, mixed> $parameters
     * @return list<array<string, mixed>>
     */
    public function select(string $sql, array $parameters = []): array
    {
        $rows = $this->statement($sql, $parameters)->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('Database driver returned a non-array result row.');
            }
            $result[] = $row;
        }

        return $result;
    }

    /** @param array<string|int, mixed> $parameters
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $parameters = []): ?array
    {
        $result = $this->statement($sql, $parameters)->fetch();

        return is_array($result) ? $result : null;
    }

    /** @param array<string|int, mixed> $parameters */
    public function execute(string $sql, array $parameters = []): int
    {
        return $this->statement($sql, $parameters)->rowCount();
    }

    public function lastInsertId(?string $name = null): string
    {
        $identifier = $this->pdo()->lastInsertId($name);
        if ($identifier === false) {
            throw new RuntimeException('Database driver did not return a last insert identifier.');
        }

        return $identifier;
    }

    public function driver(): string
    {
        return (string) $this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $savepoint = 'sctech_sp_' . $this->transactionDepth;

        if ($this->transactionDepth === 0) {
            $pdo->beginTransaction();
        } else {
            $pdo->exec('SAVEPOINT ' . $savepoint);
        }
        $this->transactionDepth++;

        try {
            $result = $callback($this);
            $this->transactionDepth--;
            if ($this->transactionDepth === 0) {
                $pdo->commit();
            } else {
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            }

            return $result;
        } catch (Throwable $exception) {
            $this->transactionDepth--;
            if ($this->transactionDepth === 0 && $pdo->inTransaction()) {
                $pdo->rollBack();
            } elseif ($this->transactionDepth > 0) {
                $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            }
            throw $exception;
        }
    }

    public function quoteIdentifier(string $identifier): string
    {
        $parts = explode('.', $identifier);
        foreach ($parts as &$part) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $part) !== 1) {
                throw new InvalidArgumentException('Unsafe SQL identifier rejected.');
            }
            $part = $this->driver() === 'mysql' ? '`' . $part . '`' : '"' . $part . '"';
        }

        return implode('.', $parts);
    }

    public function disconnect(): void
    {
        if ($this->transactionDepth !== 0) {
            throw new RuntimeException('Cannot disconnect while a transaction is active.');
        }

        $this->connection = null;
    }

    private function configurePdo(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        try {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        } catch (PDOException) {
            // Some drivers do not expose these attributes; security still uses bound parameters.
        }
    }
}
