<?php
require_once 'error_log.php';
// login.php - Giriş Sayfası
session_start();
include_once 'functions.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Login işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Güvenlik doğrulaması başarısız!");
        }

        // Input validasyonu
        if (empty($_POST['username']) || empty($_POST['password'])) {
            throw new Exception("Lütfen kullanıcı adı ve şifrenizi giriniz!");
        }

        include_once 'config.php';
        include_once 'User.php';

        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Veritabanı bağlantısı kurulamadı!");
        }        $user = new User($db);
        $user->username = htmlspecialchars(strip_tags(trim($_POST['username'])));
        // Şifreyi sadece boşluklardan temizle
        $user->password = trim($_POST['password']);

        if ($user->login()) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard.php");
            exit();        } else {
            throw new Exception($user->getError() ?: "Geçersiz kullanıcı adı veya şifre!");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; script-src 'self' cdnjs.cloudflare.com">
    <title>Giriş Yap - BikeRental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-bicycle text-primary" style="font-size: 3rem;"></i>
                            </a>
                            <h3 class="mt-3">Giriş Yap</h3>
                        </div>                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($_GET['timeout'])): ?>
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fas fa-clock"></i> Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required 
                                           autofocus value="<?php echo isset($_POST['username']) ? e($_POST['username']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">Şifre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Hesabınız yok mu? <a href="register.php">Kayıt olun</a></p>
                            <a href="index.php" class="text-muted"><small>← Ana Sayfaya Dön</small></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>