<?php
// index.php - Ana Sayfa
session_start();
include_once 'functions.php';

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <title>BikeRental - Bisiklet Kiralama Sistemi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bicycle text-primary"></i> BikeRental
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-nav navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="btn btn-outline-primary me-2">Panel</a>
                        <a href="logout.php" class="btn btn-outline-danger">Çıkış</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">Giriş</a>
                        <a href="register.php" class="btn btn-primary">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Bisiklet Kiralama Sistemi</h1>
            <p class="lead mb-5">Kolay, hızlı ve güvenli bir şekilde bisiklet kiralayın.</p>
            <?php if (!isLoggedIn()): ?>
                <div>
                    <a href="register.php" class="btn btn-light btn-lg me-3">Hemen Başla</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Giriş Yap</a>
                </div>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-light btn-lg">Panele Git</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-bicycle feature-icon"></i>
                            <h3>Kolay Kiralama</h3>
                            <p class="text-muted">Birkaç tıklama ile bisiklet kiralayın ve yolculuğunuza başlayın.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h3>Güvenli Ödeme</h3>
                            <p class="text-muted">Güvenli ödeme sistemi ile endişesiz kiralama yapın.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-clock feature-icon"></i>
                            <h3>7/24 Hizmet</h3>
                            <p class="text-muted">İstediğiniz zaman kiralama yapın ve bisikletinizi teslim edin.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> BikeRental. Tüm hakları saklıdır.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3">Gizlilik Politikası</a>
                    <a href="#" class="text-muted me-3">Kullanım Şartları</a>
                    <a href="#" class="text-muted">İletişim</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>