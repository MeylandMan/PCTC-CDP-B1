<?php

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'monitoring_project');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    // Instance unique (Singleton)
    private static ?Database $instance = null;

    // Connexion PDO partagée
    private PDO $connection;

    // ---------------------------------------------------------------------------
    // Constructeur privé — interdit l'instanciation directe
    // ---------------------------------------------------------------------------
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_AUTOCOMMIT         => true,

            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB] Échec de connexion : ' . $e->getMessage());
            throw new RuntimeException(
                'Impossible de se connecter à la base de données. Veuillez réessayer plus tard.'
            );
        }
    }

    // ---------------------------------------------------------------------------
    // Empêche le clonage et la désérialisation (sécurité Singleton)
    // ---------------------------------------------------------------------------
    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException('La désérialisation du Singleton Database est interdite.');
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // ---------------------------------------------------------------------------
    // Helpers de transaction
    // ---------------------------------------------------------------------------

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this->connection);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ---------------------------------------------------------------------------
    // Utilitaire : dernier ID inséré
    // ---------------------------------------------------------------------------
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}