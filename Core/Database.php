<?php 

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3308';
        $dbname = getenv('DB_NAME') ?: 'safebase';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}
