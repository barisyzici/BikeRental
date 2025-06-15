<?php
// delete_rental.php - Kiralama Silme
session_start();
include_once 'functions.php';
checkSession();

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once 'config.php';
include_once 'BikeRental.php';

// Debug log başlangıcı
error_log("Delete Rental - Started");

// ID kontrolü ve sanitizasyonu
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Geçersiz kiralama ID'si!";
    header("Location: list_rentals.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception("Veritabanı bağlantısı kurulamadı!");
    }
    
    $rental = new BikeRental($db);
    $rental_data = $rental->getById(intval($_GET['id']));

    error_log("Retrieved rental data: " . json_encode($rental_data));

    if (!$rental_data || $rental_data['user_id'] != $_SESSION['user_id']) {
        throw new Exception("Bu kiralama kaydına erişim izniniz yok!");
    }

    $message = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF token kontrolü
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Güvenlik doğrulaması başarısız!");
        }

        if (isset($_POST['confirm_delete'])) {
            error_log("Delete confirmation received for rental ID: " . $_GET['id']);
            
            if ($rental->delete(intval($_GET['id']))) {
                $status_text = $rental_data['status'] === 'active' ? 'iptal edildi ve ' : '';
                $_SESSION['message'] = "Kiralama kaydı {$status_text}başarıyla silindi.";
                error_log("Rental successfully deleted");
                header("Location: list_rentals.php");
                exit();
            } else {
                throw new Exception($rental->getError() ?: 'Kiralama silinirken bir hata oluştu!');
            }
        } else {
            throw new Exception("Silme işlemi onaylanmadı!");
        }
    }

} catch (Exception $e) {
    error_log("Delete Rental Error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiralama Sil - BikeRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-trash-alt text-danger"></i> 
                            Kiralama Sil
                        </h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> 
                                <?php echo e($error); ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="list_rentals.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Listeye Dön
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Kiralama Detayları:</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Bisiklet:</strong><br>
                                        <?php echo e($rental_data['bike_brand']); ?> <?php echo e($rental_data['bike_model']); ?></p>
                                        
                                        <p><strong>Müşteri:</strong><br>
                                        <?php echo e($rental_data['customer_name']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Başlangıç:</strong><br>
                                        <?php echo formatDate($rental_data['start_date']); ?></p>
                                        
                                        <p><strong>Durum:</strong><br>
                                        <?php echo formatStatus($rental_data['status']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <?php if ($rental_data['status'] === 'active'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Dikkat!</strong> Bu aktif bir kiralama kaydıdır. 
                                    Silme işlemi otomatik olarak kiralamayı iptal edecek ve kaydı silecektir.
                                </div>
                            <?php endif; ?>

                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Uyarı!</strong> Bu işlem geri alınamaz! 
                                Kiralama kaydını silmek istediğinizden emin misiniz?
                            </div>

                            <form method="POST" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="d-flex justify-content-between">
                                    <a href="list_rentals.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Vazgeç
                                    </a>
                                    <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> Evet, Sil
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
</body>
</html>