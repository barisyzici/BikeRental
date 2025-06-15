<?php
/**
 * functions.php - Yardımcı Fonksiyonlar
 * Güvenlik ve yardımcı fonksiyonlar koleksiyonu
 */

/**
 * Oturum kontrolü yapar ve geçerli değilse login sayfasına yönlendirir
 */
function checkSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        header("Location: login.php");
        exit();
    }

    // Oturum zaman aşımı kontrolü (30 dakika)
    if (time() - $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }

    // Kullanıcı bilgilerinin güncelliğini kontrol et
    try {
        include_once 'config.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            session_unset();
            session_destroy();
            header("Location: login.php?invalid=1");
            exit();
        }
    } catch (Exception $e) {
        error_log("Oturum kontrolü sırasında hata: " . $e->getMessage());
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Kullanıcının oturum durumunu kontrol eder
 * @return bool
 */
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity'] <= 1800);
}

/**
 * Tarihi formatlar
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    if (empty($date)) return '';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        error_log("Tarih formatlama hatası: " . $e->getMessage());
        return '';
    }
}

/**
 * Para birimini formatlar
 * @param float $price
 * @return string
 */
function formatPrice($price) {
    if (!is_numeric($price)) return '0,00 ₺';
    return number_format($price, 2, ',', '.') . ' ₺';
}

/**
 * Durum badge'ini döndürür
 * @param string $status
 * @return string
 */
function getStatusBadge($status) {
    $status = strtolower(trim($status));
    $badges = [
        'active' => ['class' => 'bg-success', 'text' => 'Aktif'],
        'completed' => ['class' => 'bg-primary', 'text' => 'Tamamlandı'],
        'cancelled' => ['class' => 'bg-danger', 'text' => 'İptal Edildi']
    ];

    if (!isset($badges[$status])) {
        return '<span class="badge bg-secondary">Bilinmiyor</span>';
    }

    return sprintf(
        '<span class="badge %s">%s</span>',
        htmlspecialchars($badges[$status]['class']),
        htmlspecialchars($badges[$status]['text'])
    );
}

/**
 * Input verilerini temizler ve güvenli hale getirir
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * CSRF token oluşturur
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrulaması yapar
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * XSS korumalı çıktı
 * @param string $str
 * @return string
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}