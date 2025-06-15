<?php
class User {
    private $conn;
    private $table_name = "users";
    private $error_message;

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $phone;
    public $created_at;
    public $last_login;
    public $is_active;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getError() {
        return $this->error_message;
    }

    public function register() {
        try {
            // E-posta kontrolü
            if ($this->emailExists()) {
                $this->error_message = "Bu e-posta adresi zaten kullanılıyor.";
                return false;
            }

            // Kullanıcı adı kontrolü
            if ($this->usernameExists()) {
                $this->error_message = "Bu kullanıcı adı zaten kullanılıyor.";
                return false;
            }

            $query = "INSERT INTO " . $this->table_name . " 
                     (username, email, password, full_name, phone, is_active, created_at) 
                     VALUES (:username, :email, :password, :full_name, :phone, :is_active, NOW())";

            $stmt = $this->conn->prepare($query);            // Input validation - PHP 8.1+ uyumlu
            $this->username = htmlspecialchars(strip_tags(trim($this->username)));
            $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
            $this->full_name = htmlspecialchars(strip_tags(trim($this->full_name)));
            $this->phone = htmlspecialchars(strip_tags(trim($this->phone)));

            // Şifre güvenlik kontrolü
            if (strlen($this->password) < 8) {
                $this->error_message = "Şifre en az 8 karakter olmalıdır.";
                return false;
            }            // Gelen şifrenin hash olup olmadığını kontrol et
            if (strlen($this->password) === 60 && strpos($this->password, '$2y$') === 0) {
                // Eğer gelen şifre zaten hash ise, doğrudan kullan
                $password_hash = $this->password;
            } else {
                // Değilse, yeni hash oluştur
                $clean_password = trim($this->password);
                $password_hash = password_hash($clean_password, PASSWORD_DEFAULT, ['cost' => 12]);
            }
            error_log("Kayıt - Şifre hash uzunluğu: " . strlen($password_hash));

            // Parametreleri bind et
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":full_name", $this->full_name);
            $stmt->bindParam(":phone", $this->phone);
            $is_active = true;
            $stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);

            if($stmt->execute()) {
                return true;
            }

            $this->error_message = "Kayıt işlemi sırasında bir hata oluştu.";
            return false;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            return false;
        }
    }    public function login() {
        try {
            error_log("Login denemesi başladı - Kullanıcı: " . $this->username);
            
            $query = "SELECT id, username, password, full_name, is_active 
                     FROM " . $this->table_name . " 
                     WHERE username = :username 
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Kullanıcı bulundu: " . $row['username']);
                
                if (!$row['is_active']) {
                    $this->error_message = "Hesabınız aktif değil.";
                    error_log("Hesap aktif değil: " . $row['username']);
                    return false;
                }
                  // Şifre kontrolü
                $password_to_verify = trim($this->password);
                
                // Eğer gelen şifre zaten hash ise ve veritabanındaki hash ile aynıysa
                if (strlen($password_to_verify) === 60 && strpos($password_to_verify, '$2y$') === 0) {
                    $verify_result = ($password_to_verify === $row['password']);
                } else {
                    // Normal şifre kontrolü
                    $verify_result = password_verify($password_to_verify, $row['password']);
                }
                
                error_log("Şifre kontrolü - Girilen: " . substr($password_to_verify, 0, 3) . "... (uzunluk: " . strlen($password_to_verify) . ")");
                error_log("Şifre kontrolü - DB'deki: " . substr($row['password'], 0, 3) . "... (uzunluk: " . strlen($row['password']) . ")");
                error_log("Şifre kontrolü sonucu: " . ($verify_result ? "başarılı" : "başarısız"));
                
                if($verify_result) {
                    // Kullanıcı bilgilerini set et
                    $this->id = $row['id'];
                    $this->full_name = $row['full_name'];
                    
                    // Son giriş zamanını güncelle
                    $update = "UPDATE " . $this->table_name . " 
                              SET last_login = NOW() 
                              WHERE id = :id";
                    $stmt = $this->conn->prepare($update);
                    $stmt->bindParam(':id', $this->id);
                    $stmt->execute();

                    return true;
                }
                
                $this->error_message = "Geçersiz şifre. Lütfen şifrenizi kontrol edin ve tekrar deneyin.";
                return false;
            }

            $this->error_message = "Kullanıcı bulunamadı.";
            return false;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            return false;
        }
    }

    public function usernameExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            return true; // Güvenlik için true dön
        }
    }

    public function emailExists() {
        try {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $this->email);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            return true; // Güvenlik için true dön
        }
    }

    public function updateLastLogin() {
        try {
            $query = "UPDATE " . $this->table_name . " 
                     SET last_login = NOW() 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            return false;
        }
    }
}
?>