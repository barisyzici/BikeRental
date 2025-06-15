<?php
// dashboard.php - Ana Panel
session_start();
include_once 'functions.php';
checkSession();

include_once 'config.php';
include_once 'BikeRental.php';

// CSRF token kontrolü ekle
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Veritabanı bağlantısı kurulamadı!");
    }
    
    $rental = new BikeRental($db);
    
    // Son kiralamaları getir - Performans için LIMIT ekle
    $stmt = $rental->readByUser($_SESSION['user_id'], 50); // Son 50 kayıt
    if (!$stmt) {
        throw new Exception("Kiralama verileri alınamadı!");
    }
    
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // İstatistikler - Performans iyileştirmesi
    $stats = [
        'total' => 0,
        'active' => 0,
        'completed' => 0,
        'total_spent' => 0.0
    ];

    foreach ($rentals as $r) {
        $stats['total']++;
        if ($r['status'] == 'active') $stats['active']++;
        if ($r['status'] == 'completed') $stats['completed']++;
        
        // Toplam tutarı price_per_hour ve kiralama süresine göre hesapla
        if ($r['status'] == 'completed' && !empty($r['end_date'])) {
            $hours = ceil((strtotime($r['end_date']) - strtotime($r['start_date'])) / 3600);
            $stats['total_spent'] += floatval($r['price_per_hour']) * $hours;
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Sistem hatası: " . $e->getMessage();
    header("Location: error.php");
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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com">
    <title>Panel - BikeRental</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        body {
            background-color: #f8f9fc;
        }
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 1rem;
            color: white;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            margin: 0.2rem 1rem;
            border-radius: 0.35rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 1.5rem;
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
        }
        .stat-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .table {
            background: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15);
        }
        .table th {
            background-color: #f8f9fc;
            border-top: none;
        }
        .top-bar {
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.35rem;
        }
        .btn-circle {
            border-radius: 100%;
            width: 2.5rem;
            height: 2.5rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <i class="fas fa-bicycle fa-3x mb-2"></i>
            <h5>BikeRental</h5>
        </div>
        <nav>
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                Panel
            </a>
            <a href="add_rental.php" class="nav-link">
                <i class="fas fa-fw fa-plus-circle"></i>
                Yeni Kiralama
            </a>
            <a href="list_rentals.php" class="nav-link">
                <i class="fas fa-fw fa-list"></i>
                Kiralamalar
            </a>
            <hr class="sidebar-divider">
            <a href="logout.php" class="nav-link">
                <i class="fas fa-fw fa-sign-out-alt"></i>
                Çıkış Yap
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Panel</h4>
            <div class="d-flex align-items-center">
                <span class="me-3">Hoş geldin, <strong><?php echo e($_SESSION['full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-circle btn-light">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
      <!-- İstatistik kartları -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-start border-5 border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo e($stats['total']); ?></h3>
                            <p class="text-muted mb-0">Toplam Kiralama</p>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-bicycle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-start border-5 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo e($stats['active']); ?></h3>
                            <p class="text-muted mb-0">Aktif Kiralama</p>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-start border-5 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo e($stats['completed']); ?></h3>
                            <p class="text-muted mb-0">Tamamlanan</p>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card border-start border-5 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h3 class="mb-0"><?php echo formatPrice($stats['total_spent']); ?></h3>
                            <p class="text-muted mb-0">Toplam Kazanç</p>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Hızlı İşlemler</h5>
                    <a href="add_rental.php" class="btn btn-primary me-2">
                        <i class="fas fa-plus-circle me-2"></i>Yeni Kiralama
                    </a>
                    <a href="list_rentals.php" class="btn btn-info me-2">
                        <i class="fas fa-list me-2"></i>Tüm Kiralamalar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Kiralamalar -->    <?php if (!empty($rentals)): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Son Kiralamalar</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Bisiklet</th>
                            <th>Müşteri</th>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rentals as $r): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3">
                                    <i class="fas fa-bicycle text-primary"></i>
                                </div>
                                <div>
                                    <strong><?php echo e($r['bike_brand']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($r['bike_model']); ?> - <?php echo e($r['bike_color']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo e($r['customer_name']); ?></strong><br>
                                <small class="text-muted"><?php echo e($r['customer_phone']); ?></small>
                            </div>
                        </td>
                        <td><?php echo e(formatDate($r['start_date'])); ?></td>
                        <td><?php echo e(formatDate($r['end_date'])); ?></td>
                        <td>
                            <div>
                                <strong><?php echo formatPrice($r['total_cost']); ?></strong><br>
                                <small class="text-muted"><?php echo formatPrice($r['price_per_hour']); ?>/saat</small>
                            </div>
                        </td>
                        <td><?php echo getStatusBadge($r['status']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="view_rental.php?id=<?php echo e($r['id']); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Görüntüle">
                                    <i class="fas fa-eye text-primary"></i>
                                </a>
                                <?php if ($r['status'] == 'active'): ?>
                                <a href="edit_rental.php?id=<?php echo e($r['id']); ?>" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Düzenle">
                                    <i class="fas fa-edit text-warning"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bu kiralama kaydını silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="rental_id" value="<?php echo e($r['id']); ?>">
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-light" data-bs-toggle="tooltip" title="Sil">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($rentals) >= 50): ?>
            <div class="text-center mt-3">
                <a href="list_rentals.php" class="btn btn-outline-primary">
                    Tümünü Görüntüle <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-bicycle fa-3x text-muted mb-3"></i>
            <h5>Henüz Kiralama Kaydı Yok</h5>
            <p class="text-muted">İlk kiralama kaydınızı oluşturmak için "Yeni Kiralama" butonunu kullanabilirsiniz.</p>
            <a href="add_rental.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Yeni Kiralama
            </a>
        </div>
    </div>
    <?php endif; ?>            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo e($_SESSION['csrf_token']); ?>">
        // Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Form güvenliği
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!this.querySelector('input[name="csrf_token"]')) {
                        e.preventDefault();
                        alert('Güvenlik doğrulaması başarısız!');
                    }
                });
            });
        });
    </script>
</body>
</html>