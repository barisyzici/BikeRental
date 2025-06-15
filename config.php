<?php
// Veritabanı konfigürasyon sınıfı
class Database {
    // Veritabanı bağlantı bilgileri
    private $host = 'localhost';
    private $db_name = 'bike_rental';
    private $username = 'root';
    private $password = '';
    private $conn;
    private $error;

    // Hata mesajını almak için yeni method
    public function getError() {
        return $this->error;
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
            ];

            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                $options
            );

            return $this->conn;

        } catch(PDOException $exception) {
            $this->error = $exception->getMessage();
            // Hatayı loglama
            error_log("Veritabanı bağlantı hatası: " . $this->error);
            return false;
        }
    }
}