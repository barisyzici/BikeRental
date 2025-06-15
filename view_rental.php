<?php
// view_rental.php - Kiralama Detayı Görüntüleme
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
$rental_data = null;

try {
    // ID kontrolü ve sanitizasyonu
    if (!isset($_GET['id'])) {
        throw new Exception("Kiralama ID'si belirtilmedi!");
    }

    $rental_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($rental_id === false) {
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

    // Süre ve ücret hesaplama
    $start_date = new DateTime($rental_data['start_date']);
    $end_date = !empty($rental_data['end_date']) ? new DateTime($rental_data['end_date']) : new DateTime();
    $duration = $start_date->diff($end_date);
    
    // Toplam saat hesaplama (dakikaları da dahil et)
    $total_hours = ($duration->days * 24) + $duration->h;
    if ($duration->i > 0) $total_hours++; // Başlanan saati tam sayıyoruz
    
    // Toplam ücreti hesapla
    if ($rental_data['status'] == 'completed') {
        $total_cost = $rental_data['total_cost'];
    } else {
        $total_cost = $total_hours * $rental_data['price_per_hour'];
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: list_rentals.php");
    exit();
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
    <title>Kiralama Detayı #<?php echo e($rental_data['id']); ?> - BikeRental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- ... existing navigation code ... -->

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo e($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?php echo e($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-eye"></i> Kiralama Detayı #<?php echo e($rental_data['id']); ?>
                        </h5>
                        <div>
                            <?php if ($rental_data['status'] === 'active'): ?>
                            <a href="edit_rental.php?id=<?php echo e($rental_data['id']); ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Düzenle
                            </a>
                            <?php endif; ?>
                            <a href="list_rentals.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Geri Dön
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- ... existing card content with e() function ... -->

                        <!-- İşlemler -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-cogs"></i> İşlemler</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($rental_data['status'] === 'active'): ?>
                                    <form method="POST" action="complete_rental.php" class="d-grid gap-2" 
                                          onsubmit="return confirm('Kiralama işlemini tamamlamak istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="rental_id" value="<?php echo e($rental_data['id']); ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Kiralama Tamamla
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="delete_rental.php" class="d-grid gap-2 mt-2" 
                                          onsubmit="return confirm('Bu kiralama kaydını silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="rental_id" value="<?php echo e($rental_data['id']); ?>">
                                        <a href="edit_rental.php?id=<?php echo e($rental_data['id']); ?>" class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Sil
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ... rest of the existing code ... -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>