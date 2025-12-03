<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'slim_and_native';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo " Database connected successfully<br>";
        } catch(PDOException $exception) {
            echo " Database connection error: " . $exception->getMessage() . "<br>";
        }
        return $this->conn;
    }
}