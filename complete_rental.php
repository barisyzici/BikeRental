<?php
// complete_rental.php - Kiralama Tamamlama
session_start();
include_once 'functions.php';
checkSession();

include_once 'config.php';
include_once 'BikeRental.php';

// Timezone ayarı
date_default_timezone_set('Europe/Berlin');

// Debug log başlangıcı
error_log("Complete Rental Debug - Started - ID: " . ($_GET['id'] ?? 'not set'));

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$rental_data = null;
$total_cost = 0;
$hours = 0;
$estimated_cost = 0;
$early_warning = false;

try {
    // ID kontrolü
    if (!isset($_GET['id'])) {
        throw new Exception('Kiralama ID\'si belirtilmedi!');
    }

    $rental_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($rental_id === false || $rental_id <= 0) {
        throw new Exception('Geçersiz kiralama ID\'si!');
    }

    $database = new Database();
    $db = $database->getConnection();
    $rental = new BikeRental($db);
    $rental_data = $rental->getById($rental_id);

    error_log("Retrieved rental data: " . json_encode($rental_data));

    if (!$rental_data) {
        throw new Exception('Kiralama kaydı bulunamadı!');
    }

    if ($rental_data['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Bu kiralama kaydına erişim izniniz yok!');
    }

    if ($rental_data['status'] != 'active') {
        throw new Exception('Bu kiralama zaten tamamlanmış veya iptal edilmiş!');
    }

    // POST request kontrolü - Tamamlama işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Güvenlik doğrulaması başarısız!');
        }

        // Şu anki zaman
        $current_time = time();
        $start_time = strtotime($rental_data['start_date']);
        
        if ($start_time === false) {
            error_log("Invalid start date format: " . $rental_data['start_date']);
            throw new Exception('Başlangıç tarihi geçersiz format!');
        }

        // Debug için tarih bilgilerini logla
        error_log("Current server time: " . date('Y-m-d H:i:s', $current_time));
        error_log("Start date from DB: " . $rental_data['start_date']);
        error_log("Parsed start time: " . date('Y-m-d H:i:s', $start_time));

        // Erken tamamlama durumu
        if ($current_time < $start_time) {
            error_log("Erken tamamlama tespit edildi - planlanan başlangıç: " . date('Y-m-d H:i:s', $start_time));
            // Başlangıç zamanını son 1 saat içindeyse kabul et
            $hours_until_start = ($start_time - $current_time) / 3600;
            if ($hours_until_start <= 1) {
                $start_time = $current_time;
                $rental_data['start_date'] = date('Y-m-d H:i:s', $current_time);
                error_log("Başlangıç zamanı güncellendi: " . $rental_data['start_date']);
            } else {
                error_log("Çok erken tamamlama denemesi: " . $hours_until_start . " saat var");
                throw new Exception('Kiralama başlangıcına ' . ceil($hours_until_start) . ' saat var, henüz tamamlanamaz!');
            }
        }

        // Bitiş zamanı
        $end_time = $current_time;
        $end_date = date('Y-m-d H:i:s', $end_time);
        
        error_log("Final times - Start: " . $rental_data['start_date'] . ", End: " . $end_date);

        // Süre ve ücret hesaplama
        $diff_seconds = $end_time - $start_time;
        
        // Saat hesaplama - 15 dakikadan az ise 0.25 saat, 30 dakikadan az ise 0.5 saat, 
        // 45 dakikadan az ise 0.75 saat, üstü için tam saat
        $minutes = $diff_seconds / 60;
        if ($minutes < 15) {
            $diff_hours = 0.25;
        } elseif ($minutes < 30) {
            $diff_hours = 0.5;
        } elseif ($minutes < 45) {
            $diff_hours = 0.75;
        } else {
            $diff_hours = ceil($diff_seconds / 3600);
        }

        // Minimum 1 saat ücreti uygula
        $total_cost = max(floatval($rental_data['price_per_hour']), 
                         $diff_hours * floatval($rental_data['price_per_hour']));

        error_log("Time difference: " . $diff_seconds . " seconds (" . $minutes . " minutes)");
        error_log("Calculated hours: " . $diff_hours);
        error_log("Price per hour: " . $rental_data['price_per_hour']);
        error_log("Calculated total cost: " . $total_cost);

        // Güncelleme işlemi
        if ($rental->update(
            $rental_data['id'],
            $rental_data['bike_brand'],
            $rental_data['bike_model'],
            $rental_data['bike_color'],
            $rental_data['price_per_hour'],
            $rental_data['customer_name'],
            $rental_data['customer_phone'],
            $rental_data['customer_email'],
            $rental_data['customer_id_number'],
            $rental_data['start_date'],
            $end_date,
            'completed',
            $total_cost,
            $rental_data['notes']
        )) {
            $_SESSION['success'] = sprintf(
                'Kiralama başarıyla tamamlandı! Toplam süre: %.2f saat, Toplam tutar: %.2f TL',
                $diff_hours,
                $total_cost
            );
            error_log("Kiralama başarıyla tamamlandı. ID: {$rental_id}, Saat: {$diff_hours}, Tutar: {$total_cost}");
            header("Location: list_rentals.php");
            exit();
        } else {
            $error_msg = $rental->getError();
            error_log("Güncelleme hatası: " . $error_msg);
            throw new Exception("Kiralama tamamlanırken bir hata oluştu: " . $error_msg);
        }
    }

    // Ön bilgileri hesapla (form gösterimi için)
    $start_time = strtotime($rental_data['start_date']);
    $current_time = time();
    
    if ($current_time < $start_time) {
        // Erken tamamlama durumu
        $hours_until_start = ($start_time - $current_time) / 3600;
        if ($hours_until_start <= 1) {
            $hours = 1;
            $total_cost = floatval($rental_data['price_per_hour']);
            $early_warning = true;
        } else {
            throw new Exception('Kiralama başlangıcına ' . ceil($hours_until_start) . ' saat var, henüz tamamlanamaz!');
        }
    } else {
        // Normal durum - geçen süreyi hesapla
        $diff_seconds = $current_time - $start_time;
        $minutes = $diff_seconds / 60;
        
        if ($minutes < 15) {
            $hours = 0.25;
        } elseif ($minutes < 30) {
            $hours = 0.5;
        } elseif ($minutes < 45) {
            $hours = 0.75;
        } else {
            $hours = ceil($diff_seconds / 3600);
        }
        
        $total_cost = max(floatval($rental_data['price_per_hour']), 
                         $hours * floatval($rental_data['price_per_hour']));
    }
    
    $estimated_cost = $total_cost;
    error_log("Tahmini kiralama süresi: {$hours} saat, tutar: {$estimated_cost}");

} catch (Exception $e) {
    error_log("Complete Rental Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiralama Tamamla - BikeRental</title>
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
                            <i class="fas fa-flag-checkered"></i> 
                            Kiralama Tamamla
                        </h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> 
                                <?php echo e($error); ?>
                            </div>
                            <div class="text-center mt-4">
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
                                        <p><strong><i class="fas fa-bicycle"></i> Bisiklet:</strong><br>
                                        <?php echo e($rental_data['bike_brand']); ?> <?php echo e($rental_data['bike_model']); ?> (<?php echo e($rental_data['bike_color']); ?>)</p>
                                        
                                        <p><strong><i class="fas fa-user"></i> Müşteri:</strong><br>
                                        <?php echo e($rental_data['customer_name']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong><i class="fas fa-clock"></i> Başlangıç:</strong><br>
                                        <?php echo formatDate($rental_data['start_date']); ?></p>
                                        
                                        <p><strong><i class="fas fa-money-bill-wave"></i> Saatlik Ücret:</strong><br>
                                        <?php echo formatPrice($rental_data['price_per_hour']); ?> TL</p>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-primary">
                                <h5><i class="fas fa-calculator"></i> Tahmini Ücret</h5>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <strong>Süre</strong><br>
                                        <?php echo $hours; ?> Saat
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Birim Fiyat</strong><br>
                                        <?php echo formatPrice($rental_data['price_per_hour']); ?> TL/Saat
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Toplam</strong><br>
                                        <?php echo formatPrice($estimated_cost); ?> TL
                                    </div>
                                </div>
                                <div class="mt-2 small text-muted text-center">
                                    <i class="fas fa-info-circle"></i> 
                                    Not: Final ücret, tamamlama anındaki süreye göre hesaplanacaktır.
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Dikkat!</strong> Bu kiralama kaydını tamamlamak istediğinizden emin misiniz? 
                                Bu işlem geri alınamaz.
                            </div>

                            <form method="POST" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="d-flex justify-content-between">
                                    <a href="list_rentals.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> İptal
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Tamamla ve Ödeme Al
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