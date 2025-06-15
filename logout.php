<?php
// logout.php - Çıkış İşlemi
session_start();

try {
    // Son aktivite zamanını güncelle (varsa)
    if (isset($_SESSION['user_id'])) {
        include_once 'config.php';
        $database = new Database();
        if ($db = $database->getConnection()) {
            $query = "UPDATE users SET last_login = NOW() WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
        }
    }

    // CSRF token kontrolü
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Güvenlik doğrulaması başarısız!");
        }
    }

    // Tüm session verilerini temizle
    $_SESSION = array();

    // Session cookie'sini güvenli bir şekilde sil
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params["path"],
                'domain' => $params["domain"],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    // Session'ı sonlandır
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Başarılı çıkış mesajı
    session_start();
    $_SESSION['message'] = 'Başarıyla çıkış yapıldı.';

    // Giriş sayfasına güvenli yönlendirme
    header("Location: login.php");
    exit();

} catch (Exception $e) {
    // Hata durumunda güvenli yönlendirme
    session_start();
    $_SESSION['error'] = $e->getMessage();
    header("Location: login.php");
    exit();
}
?>