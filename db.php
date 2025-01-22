<?php

class Db {
    private $host = 'localhost';
    private $username = 'root';  // Replace with your database username
    private $password = '';  // Replace with your database password
    private $dbname = 'students'; // Replace with your database name

    private $conn;
    private $stmt;

    public function __construct() {
        // Initialize the connection when the object is created
        $this->connect();
    }

    private function connect() {
        // Set the DSN (Data Source Name)
        $dsn = "mysql:host=$this->host;dbname=$this->dbname;charset=utf8";

        try {
            // Create a new PDO instance
            $this->conn = new PDO($dsn, $this->username, $this->password);
            // Set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        // Return the PDO connection object
        return $this->conn;
    }

    public function prepare($sql) {
        // Prepare a statement
        $this->stmt = $this->conn->prepare($sql);
        return $this->stmt;
    }

    public function bind($param, $value, $type = null) {
        // Bind parameters to the statement
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        // Execute the prepared statement
        return $this->stmt->execute();
    }

    public function fetch() {
        // Fetch a single row from the result set
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll() {
        // Fetch all rows from the result set
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function close() {
        // Close the database connection
        $this->conn = null;
    }
}

?>
