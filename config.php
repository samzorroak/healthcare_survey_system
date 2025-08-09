<?php
// $host = 'localhost'; // Database host
// $dbname = 'survey_system'; // Database name
// $username = 'root'; // Database username
// $password = ''; // Database password (leave empty if no password)

// // Set the DSN (Data Source Name) for PDO
// $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

// // Set PDO options
// $options = [
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
// ];

// // Create the PDO instance (Database connection)
// try {
//     $conn = new PDO($dsn, $username, $password, $options);
// } catch (PDOException $e) {
//     // Catch connection errors
//     die("Connection failed: " . $e->getMessage());
// }

class Database {
    private static $instance = null;
    private $conn;

    private $host = 'localhost';
    private $dbname = 'survey_system';
    private $username = 'root';
    private $password = '';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}

?>
