<?php
// edit_rental.php - Kiralama Düzenleme
session_start();
include_once 'functions.php';
checkSession();

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once 'config.php';
include_once 'BikeRental.php';

$error = '';
$message = '';
$rental_data = null;

try {
    // ID kontrolü
    if (!isset($_GET['id'])) {
        throw new Exception("Kiralama ID'si belirtilmedi!");
    }

    $rental_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($rental_id === false || $rental_id <= 0) {
        throw new Exception("Geçersiz kiralama ID'si!");
    }

    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Veritabanı bağlantısı kurulamadı!");
    }

    $rental = new BikeRental($db);
    $rental_data = $rental->getById($rental_id);

    if (!$rental_data) {
        throw new Exception("Kiralama kaydı bulunamadı: " . $rental->getError());
    }

    if ($rental_data['user_id'] != $_SESSION['user_id']) {
        throw new Exception("Bu kiralama kaydına erişim izniniz yok!");
    }

    if ($rental_data['status'] !== 'active') {
        throw new Exception("Sadece aktif kiralamalar düzenlenebilir!");
    }

    // POST request işleme
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Güvenlik doğrulaması başarısız!");
        }

        // Input validasyonu ve sanitizasyonu
        $bike_brand = htmlspecialchars(strip_tags(trim($_POST['bike_brand'])));
        $bike_model = htmlspecialchars(strip_tags(trim($_POST['bike_model'])));
        $bike_color = htmlspecialchars(strip_tags(trim($_POST['bike_color'])));
        $price_per_hour = filter_var($_POST['price_per_hour'], FILTER_VALIDATE_FLOAT);
        $customer_name = htmlspecialchars(strip_tags(trim($_POST['customer_name'])));
        $customer_phone = htmlspecialchars(strip_tags(trim($_POST['customer_phone'])));
        $customer_email = filter_var(trim($_POST['customer_email']), FILTER_VALIDATE_EMAIL);
        $customer_id_number = htmlspecialchars(strip_tags(trim($_POST['customer_id_number'])));
        $start_date = $_POST['start_date'];
        $notes = htmlspecialchars(strip_tags(trim($_POST['notes'])));

        // Validasyon kontrolleri
        if (empty($bike_brand) || empty($bike_model)) {
            throw new Exception("Bisiklet marka ve model bilgileri gereklidir!");
        }

        if (empty($customer_name) || empty($customer_phone)) {
            throw new Exception("Müşteri adı ve telefon bilgileri gereklidir!");
        }

        if (!$price_per_hour || $price_per_hour <= 0) {
            throw new Exception("Geçerli bir saatlik ücret giriniz!");
        }

        if (!strtotime($start_date)) {
            throw new Exception("Geçerli bir başlangıç tarihi giriniz!");
        }

        // Güncelleme işlemi
        if ($rental->update(
            $rental_id,
            $bike_brand,
            $bike_model,
            $bike_color,
            $price_per_hour,
            $customer_name,
            $customer_phone,
            $customer_email,
            $customer_id_number,
            $start_date,
            $rental_data['end_date'],
            $rental_data['status'],
            $rental_data['total_cost'],
            $notes
        )) {
            $_SESSION['success'] = "Kiralama kaydı başarıyla güncellendi.";
            header("Location: list_rentals.php");
            exit();
        } else {
            throw new Exception("Güncelleme sırasında bir hata oluştu: " . $rental->getError());
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiralama Düzenle - BikeRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Kiralama Düzenle</h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                            <div class="text-center">
                                <a href="list_rentals.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Listeye Dön
                                </a>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Bisiklet Markası</label>
                                        <input type="text" name="bike_brand" class="form-control" required 
                                               value="<?php echo e($rental_data['bike_brand']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Model</label>
                                        <input type="text" name="bike_model" class="form-control" required 
                                               value="<?php echo e($rental_data['bike_model']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Renk</label>
                                        <input type="text" name="bike_color" class="form-control" required 
                                               value="<?php echo e($rental_data['bike_color']); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Müşteri Adı</label>
                                        <input type="text" name="customer_name" class="form-control" required 
                                               value="<?php echo e($rental_data['customer_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Telefon</label>
                                        <input type="tel" name="customer_phone" class="form-control" required 
                                               value="<?php echo e($rental_data['customer_phone']); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" name="customer_email" class="form-control"
                                               value="<?php echo e($rental_data['customer_email']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kimlik No</label>
                                        <input type="text" name="customer_id_number" class="form-control" required 
                                               value="<?php echo e($rental_data['customer_id_number']); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Başlangıç Tarihi</label>
                                        <input type="datetime-local" name="start_date" class="form-control" required 
                                               value="<?php echo date('Y-m-d\TH:i', strtotime($rental_data['start_date'])); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Saatlik Ücret (TL)</label>
                                        <input type="number" name="price_per_hour" class="form-control" required step="0.01" min="0"
                                               value="<?php echo e($rental_data['price_per_hour']); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notlar</label>
                                    <textarea name="notes" class="form-control" rows="3"><?php echo e($rental_data['notes']); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="list_rentals.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> İptal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Kaydet
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validasyonu için
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>