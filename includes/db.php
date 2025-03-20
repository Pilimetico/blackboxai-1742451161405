<?php
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        require_once __DIR__ . '/../config/config.php';

        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function getLastError() {
        return $this->connection->error;
    }

    public function getLastId() {
        return $this->connection->insert_id;
    }

    public function beginTransaction() {
        $this->connection->begin_transaction();
    }

    public function commit() {
        $this->connection->commit();
    }

    public function rollback() {
        $this->connection->rollback();
    }

    // Prevenir clonación del objeto
    private function __clone() { }

    // Prevenir deserialización del objeto
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}