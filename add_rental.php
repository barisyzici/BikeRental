<?php
// add_rental.php - Yeni Kiralama Ekleme
session_start();
include_once 'functions.php';
checkSession();

include_once 'config.php';
include_once 'BikeRental.php';

// CSRF token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_POST) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF doğrulama hatası!');
    }

    // Kullanıcı ID kontrolü
    if (empty($_SESSION['user_id'])) {
        $message = '<div class="alert alert-danger">Oturum süreniz dolmuş olabilir. Lütfen tekrar giriş yapın.</div>';
    }
    // Tarih kontrolü
    else if (empty($_POST['rental_start_date']) || empty($_POST['rental_end_date'])) {
        $message = '<div class="alert alert-danger">Başlangıç ve bitiş tarihleri gereklidir!</div>';
    }
    else {
        $start_date = strtotime($_POST['rental_start_date']);
        $end_date = strtotime($_POST['rental_end_date']);
        
        if ($end_date < $start_date) {
            $message = '<div class="alert alert-danger">Bitiş tarihi başlangıç tarihinden önce olamaz!</div>';
        }
        // Fiyat kontrolü
        else if (!is_numeric($_POST['price_per_hour']) || $_POST['price_per_hour'] <= 0) {
            $message = '<div class="alert alert-danger">Geçerli bir fiyat giriniz!</div>';
        }
        else {
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Önce kullanıcı ID'sinin geçerliliğini kontrol et
                $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Geçersiz kullanıcı hesabı!");
                }

                $rental = new BikeRental($db);            // Tarih ve saat formatını düzelt
                $start_date = date('Y-m-d H:i:s', strtotime($_POST['rental_start_date']));
                $end_date = date('Y-m-d H:i:s', strtotime($_POST['rental_end_date']));
                
                // Fiyatı float olarak al ve kontrol et
                $price = filter_var($_POST['price_per_hour'], FILTER_VALIDATE_FLOAT);
                if ($price === false || $price <= 0) {
                    throw new Exception("Geçersiz fiyat bilgisi!");
                }            // Kiralama kaydını oluştur
                $result = $rental->create(
                    $_SESSION['user_id'],
                    sanitizeInput($_POST['bike_brand']),
                    sanitizeInput($_POST['bike_model']),
                    sanitizeInput($_POST['bike_color']),
                    $price,
                    sanitizeInput($_POST['customer_name']),
                    sanitizeInput($_POST['customer_phone']),
                    sanitizeInput($_POST['customer_email']),
                    sanitizeInput($_POST['customer_id_number']),
                    $start_date,
                    $end_date,
                    sanitizeInput($_POST['status']),
                    0, // total_cost başlangıçta 0
                    sanitizeInput($_POST['notes'])
                );            if ($result) {
                    header("Location: dashboard.php?success=1");
                    exit();
                } else {
                    $errorMsg = $rental->getError();
                    $message = '<div class="alert alert-danger">Kayıt eklenirken bir hata oluştu: ' . 
                              htmlspecialchars($errorMsg ?: 'Bilinmeyen hata') . '</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Sistem hatası: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kiralama - BikeRental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; border-radius: 10px; }
        .navbar-brand { font-weight: bold; color: #667eea !important; }
        .form-control:focus, .form-select:focus { 
            border-color: #667eea; 
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,.25); 
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-bicycle"></i> BikeRental
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="dashboard.php">Panel</a>
            <a class="nav-link" href="logout.php">Çıkış</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus"></i> Yeni Bisiklet Kiralaması
                    </h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title mb-4">Yeni Kiralama Kaydı</h5>
                    <?php echo $message; ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="mb-3">Bisiklet Bilgileri</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Marka</label>
                                    <input type="text" name="bike_brand" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Model</label>
                                    <input type="text" name="bike_model" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Renk</label>
                                    <input type="text" name="bike_color" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="mb-3">Müşteri Bilgileri</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="customer_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" name="customer_phone" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" name="customer_email" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kimlik No</label>
                                    <input type="text" name="customer_id_number" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h6 class="mb-3">Kiralama Detayları</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Başlangıç Tarihi</label>
                                    <input type="datetime-local" name="rental_start_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Bitiş Tarihi</label>
                                    <input type="datetime-local" name="rental_end_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Saatlik Ücret (₺)</label>
                                    <input type="number" name="price_per_hour" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teslim Alma Yeri</label>
                                    <input type="text" name="pickup_location" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teslim Etme Yeri</label>
                                    <input type="text" name="return_location" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Durum</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active">Aktif</option>
                                        <option value="pending">Beklemede</option>
                                        <option value="completed">Tamamlandı</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Notlar</label>
                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="dashboard.php" class="btn btn-light me-2">İptal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // Form doğrulama
    (function () {
        'use strict'
        const forms = document.querySelectorAll('.needs-validation')
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Tarih kontrolleri
    const startDateInput = document.getElementById('rental_start_date');
    const endDateInput = document.getElementById('rental_end_date');
    const today = new Date().toISOString().split('T')[0];

    startDateInput.setAttribute('min', today);
    endDateInput.setAttribute('min', today);

    startDateInput.addEventListener('change', function() {
        endDateInput.setAttribute('min', this.value);
        if(endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });

    // Fiyat kontrolü
    document.getElementById('price_per_hour').addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
    });
</script>
</body>
</html>