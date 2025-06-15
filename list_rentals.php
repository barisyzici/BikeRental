<?php
// list_rentals.php - Kiralama Listesi
session_start();
include_once 'functions.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

error_log("Debug - User ID: " . $_SESSION['user_id']);
error_log("Debug - Session: " . print_r($_SESSION, true));

// CSRF token kontrolü
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$rentals = [];
$total_pages = 1;
$page = 1;

try {
    include_once 'config.php';
    include_once 'BikeRental.php';

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Veritabanı bağlantısı kurulamadı!");
    }

    // Veritabanı bağlantısını test et
    try {
        $test_query = $db->query("SELECT 1");
    } catch (PDOException $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        throw new Exception("Veritabanı bağlantı testi başarısız!");
    }

    // Users tablosunda kullanıcının varlığını kontrol et
    $check_user = $db->prepare("SELECT id FROM users WHERE id = ?");
    $check_user->execute([$_SESSION['user_id']]);
    if (!$check_user->fetch()) {
        error_log("User not found in database: " . $_SESSION['user_id']);
        session_unset();
        session_destroy();
        header("Location: login.php?error=invalid_user");
        exit();
    }

    $rental = new BikeRental($db);
    
    // Arama ve filtreleme parametrelerini güvenli hale getir
    $search = isset($_GET['search']) ? htmlspecialchars(strip_tags(trim($_GET['search']))) : '';
    $status_filter = isset($_GET['status']) ? htmlspecialchars(strip_tags(trim($_GET['status']))) : '';
    
    // Geçerli status değerlerini kontrol et
    $valid_statuses = ['active', 'completed', 'cancelled'];
    if (!empty($status_filter) && !in_array($status_filter, $valid_statuses)) {
        $status_filter = '';
    }

    // Sayfalama için güvenli değerler
    $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
    $page = ($page > 0) ? $page : 1;
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;

    error_log("Debug - Before getUserRentals");
    error_log("User ID: " . $_SESSION['user_id']);
    error_log("Search: " . $search);
    error_log("Status: " . $status_filter);

    // Kiralama verilerini getir
    $rentals = $rental->getUserRentals(
        $_SESSION['user_id'],
        $search,
        $status_filter,
        $records_per_page,
        $offset
    );

    error_log("Debug - After getUserRentals");
    error_log("Rentals result: " . print_r($rentals, true));

    if ($rentals === false) {
        error_log("Rental error: " . $rental->getError());
        throw new Exception("Kiralama kayıtları alınırken hata oluştu: " . $rental->getError());
    }

    // Toplam kayıt sayısını al
    $total_records = $rental->countUserRentals($_SESSION['user_id'], $search, $status_filter);
    if ($total_records === false) {
        throw new Exception("Toplam kayıt sayısı alınırken hata oluştu: " . $rental->getError());
    }

    $total_pages = ceil($total_records / $records_per_page);

} catch (Exception $e) {
    error_log("List rentals error: " . $e->getMessage());
    $message = '<div class="alert alert-danger">' . e($e->getMessage()) . '</div>';
}

// Silme işlemi kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception('Güvenlik doğrulaması başarısız!');
        }

        if (!isset($_POST['rental_id']) || !is_numeric($_POST['rental_id'])) {
            throw new Exception('Geçersiz kiralama ID\'si!');
        }

        $rental_id = intval($_POST['rental_id']);
        
        // Önce kaydın bu kullanıcıya ait olduğunu kontrol et
        $rental_data = $rental->getById($rental_id);
        if (!$rental_data || $rental_data['user_id'] != $_SESSION['user_id']) {
            throw new Exception('Bu kiralama kaydına erişim izniniz yok!');
        }

        if ($rental->delete($rental_id)) {
            $_SESSION['success'] = 'Kiralama kaydı başarıyla silindi.';
            header("Location: list_rentals.php");
            exit();
        } else {
            throw new Exception('Kiralama silinirken bir hata oluştu: ' . $rental->getError());
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">' . e($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiralama Listesi - BikeRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Kiralama Listesi</h4>
                            <a href="add_rental.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Yeni Kiralama
                            </a>
                        </div>

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo e($_SESSION['success']);
                                unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($message)): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>

                        <!-- Filtreleme Formu -->
                        <form method="GET" class="mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Ara..." 
                                           value="<?php echo e($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">Tüm Durumlar</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Tamamlandı</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>İptal Edildi</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Filtrele
                                    </button>
                                </div>
                                <?php if (!empty($search) || !empty($status_filter)): ?>
                                <div class="col-md-2">
                                    <a href="list_rentals.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Temizle
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if (empty($rentals)): ?>
                            <div class="alert alert-info">
                                Kiralama kaydı bulunamadı.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Bisiklet</th>
                                            <th>Müşteri</th>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <th>Ücret</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($rentals as $r): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo e($r['bike_brand']); ?> <?php echo e($r['bike_model']); ?></strong><br>
                                                    <small class="text-muted"><?php echo e($r['bike_color']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo e($r['customer_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo e($r['customer_phone']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo formatDate($r['start_date']); ?></td>
                                            <td><?php echo $r['end_date'] ? formatDate($r['end_date']) : '-'; ?></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo formatPrice($r['total_cost']); ?></strong><br>
                                                    <small class="text-muted"><?php echo formatPrice($r['price_per_hour']); ?>/saat</small>
                                                </div>
                                            </td>
                                            <td><?php echo getStatusBadge($r['status']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_rental.php?id=<?php echo e($r['id']); ?>" 
                                                       class="btn btn-sm btn-light" 
                                                       title="Görüntüle">
                                                        <i class="fas fa-eye text-primary"></i>
                                                    </a>
                                                    <?php if ($r['status'] == 'active'): ?>
                                                    <a href="edit_rental.php?id=<?php echo e($r['id']); ?>" 
                                                       class="btn btn-sm btn-light"
                                                       title="Düzenle">
                                                        <i class="fas fa-edit text-warning"></i>
                                                    </a>
                                                    <a href="complete_rental.php?id=<?php echo e($r['id']); ?>" 
                                                       class="btn btn-sm btn-light"
                                                       title="Tamamla"
                                                       onclick="return confirm('Bu kiralamayı tamamlamak istediğinizden emin misiniz?')">
                                                        <i class="fas fa-check text-success"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Bu kiralama kaydını silmek istediğinizden emin misiniz?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="rental_id" value="<?php echo e($r['id']); ?>">
                                                        <button type="submit" name="action" value="delete" 
                                                                class="btn btn-sm btn-light" title="Sil">
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

                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Sayfalama" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page-1); ?>&search=<?php echo e($search); ?>&status=<?php echo e($status_filter); ?>">
                                            <i class="fas fa-chevron-left"></i> Önceki
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo e($search); ?>&status=<?php echo e($status_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page+1); ?>&search=<?php echo e($search); ?>&status=<?php echo e($status_filter); ?>">
                                            Sonraki <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>