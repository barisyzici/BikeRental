<?php
require_once 'error_log.php';
// register.php - Kayıt Sayfası
include_once 'functions.php';
include_once 'config.php';
include_once 'User.php';

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$message = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF token kontrolü
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Güvenlik doğrulaması başarısız!");
        }        // Input validation ve sanitization - PHP 8.1+ uyumlu
        $username = htmlspecialchars(strip_tags(trim($_POST['username'])));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $full_name = htmlspecialchars(strip_tags(trim($_POST['full_name'])));
        $phone = htmlspecialchars(strip_tags(trim($_POST['phone'])));
        $password = trim($_POST['password']); // Sadece boşlukları temizle
        $confirm_password = trim($_POST['confirm_password']); // Sadece boşlukları temizle

        // Validasyon kontrolleri
        if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
            throw new Exception("Lütfen tüm zorunlu alanları doldurun!");
        }

        if (!$email) {
            throw new Exception("Geçerli bir e-posta adresi giriniz!");
        }        if (strlen($password) < 8) {
            throw new Exception("Şifre en az 8 karakter olmalıdır!");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Şifreler eşleşmiyor!");
        }

        $database = new Database();
        $db = $database->getConnection();
        if (!$db) {
            throw new Exception("Veritabanı bağlantısı kurulamadı!");
        }

        $user = new User($db);        $user->username = $username;
        $user->email = $email;
        $user->full_name = $full_name;
        $user->phone = $phone;
        $user->password = $password; // Ham şifreyi gönder, User sınıfı hashleyecek

        if ($user->usernameExists()) {
            throw new Exception("Bu kullanıcı adı zaten kullanılıyor!");
        }

        if ($user->emailExists()) {
            throw new Exception("Bu e-posta adresi zaten kullanılıyor!");
        }

        if ($user->register()) {
            $_SESSION['message'] = "Kayıt başarılı! Lütfen giriş yapın.";
            header("Location: login.php");
            exit();
        } else {
            throw new Exception("Kayıt sırasında bir hata oluştu!");
        }
    }
} catch (Exception $e) {
    $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com">
    <title>Kayıt Ol - BikeRental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-bicycle text-primary" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">Kayıt Ol</h3>
                        </div>

                        <?php echo $message; ?>

                        <form method="POST" id="registerForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Kullanıcı Adı *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   required pattern="^[a-zA-Z0-9_]{3,20}$" 
                                                   title="3-20 karakter arası, sadece harf, rakam ve alt çizgi kullanılabilir">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Ad Soyad *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Telefon</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   pattern="^[0-9\s\-\+]{10,15}$" 
                                                   title="Geçerli bir telefon numarası giriniz">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Şifre *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>                                            <input type="password" class="form-control" id="password" name="password" 
                                                   required minlength="8">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Şifre Tekrar *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required minlength="8">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> Kayıt Ol
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-3">
                            <p>Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a></p>
                            <a href="index.php" class="text-muted">← Ana Sayfaya Dön</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            if (password !== confirm_password) {
                e.preventDefault();
                alert('Şifreler eşleşmiyor!');
                return false;
            }            if (password.length < 8) {
                e.preventDefault();
                alert('Şifre en az 8 karakter olmalıdır!');
                return false;
            }
        });
    </script>
</body>
</html>