<?php
class Database
{
    private static $instance = null;

    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $dbname = "gym_reservation";

    public $conn;

    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Singleton instance getter
     * Returns the same database connection across the application
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * For backward compatibility with existing code
     * New code should use getInstance()
     */
    public static function getConnection(): mysqli
    {
        return self::getInstance()->conn;
    }

    private function connect()
    {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
        // Set charset for security
        $this->conn->set_charset("utf8mb4");
    }

    public function close()
    {
        if ($this->conn)
            $this->conn->close();
        self::$instance = null;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
