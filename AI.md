php kullanarak bisiklet kiralama takip sistemi yapacagim, nasil yaparim


ChatGPT said:
Bir bisiklet kiralama takip sistemi PHP kullanarak yapılabilir ve temel olarak şu ana bileşenlerden oluşur:

🚲 1. Sistem Özeti
Sistem, şu işlevleri yerine getirecek:

Bisikletleri yönetme (ekle, düzenle, sil, durumunu gör)

Kullanıcılar (müşteriler) ve personel yönetimi

Kiralama işlemleri (kirala, iade et, süresini gör)

Raporlama (günlük/aylık gelir, aktif kiralamalar)

🛠 2. Teknolojiler
PHP (backend)

MySQL (veritabanı)

HTML + CSS + JavaScript (frontend)

Opsiyonel: Bootstrap (tasarımı hızlandırır)

🧩 3. Veritabanı Tasarımı (MySQL)
sql
Copy
Edit
CREATE TABLE bikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(100),
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    price_per_hour DECIMAL(6,2)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20)
);

CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    bike_id INT,
    start_time DATETIME,
    end_time DATETIME,
    total_price DECIMAL(8,2),
    returned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bike_id) REFERENCES bikes(id)
);
🧑‍💻 4. PHP Dosya Yapısı (Önerilen)
pgsql
Copy
Edit
/bike-rental/
├── index.php
├── connect.php
├── add_bike.php
├── rent_bike.php
├── return_bike.php
├── list_bikes.php
├── rentals_report.php
├── css/
└── js/
🔌 5. Veritabanına Bağlantı (connect.php)
php
Copy
Edit
<?php
$pdo = new PDO("mysql:host=localhost;dbname=bikerental", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
🛒 6. Kiralama İşlemi (rent_bike.php)
php
Copy
Edit
<?php
require 'connect.php';

$user_id = $_POST['user_id'];
$bike_id = $_POST['bike_id'];
$start_time = date('Y-m-d H:i:s');

// Fiyat bilgisini al
$stmt = $pdo->prepare("SELECT price_per_hour FROM bikes WHERE id = ?");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();

if (!$bike) {
    die("Bisiklet bulunamadı.");
}

// Kiralama kaydı oluştur
$stmt = $pdo->prepare("INSERT INTO rentals (user_id, bike_id, start_time) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $bike_id, $start_time]);

// Bisiklet durumunu güncelle
$stmt = $pdo->prepare("UPDATE bikes SET status = 'rented' WHERE id = ?");
$stmt->execute([$bike_id]);

echo "Kiralama işlemi başarılı.";
?>
🧾 7. Bisiklet İade İşlemi (return_bike.php)
php
Copy
Edit
<?php
require 'connect.php';

$rental_id = $_POST['rental_id'];
$end_time = date('Y-m-d H:i:s');

// Kiralama bilgisi
$stmt = $pdo->prepare("SELECT start_time, bike_id FROM rentals WHERE id = ?");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch();

$duration = (strtotime($end_time) - strtotime($rental['start_time'])) / 3600; // saat cinsinden
$bike_id = $rental['bike_id'];

$stmt = $pdo->prepare("SELECT price_per_hour FROM bikes WHERE id = ?");
$stmt->execute([$bike_id]);
$bike = $stmt->fetch();

$total_price = round($duration * $bike['price_per_hour'], 2);

// Kiralama kaydını güncelle
$stmt = $pdo->prepare("UPDATE rentals SET end_time = ?, total_price = ?, returned = TRUE WHERE id = ?");
$stmt->execute([$end_time, $total_price, $rental_id]);

// Bisikleti müsait hale getir
$stmt = $pdo->prepare("UPDATE bikes SET status = 'available' WHERE id = ?");
$stmt->execute([$bike_id]);

echo "İade işlemi tamamlandı. Ücret: ₺$total_price";
?>
📊 8. Listeleme ve Raporlama
Tüm bisikletleri listele (list_bikes.php)

Aktif kiralamaları göster

Günlük/aylık raporlar

🧠 9. Ek Özellikler (Opsiyonel)
Kullanıcı girişi (admin vs müşteri)

QR kodla kiralama

Gecikme ücreti hesaplama

Google Maps entegrasyonu (lokasyon takibi)

✅ 10. Başlarken
XAMPP gibi bir sunucu kur.

MySQL ile veritabanını oluştur.

Yukarıdaki dosya yapısını kur ve çalıştır.

Basitten başla, sonra geliştirmeye devam et.

İstersen bu sistemi adım adım birlikte yazabiliriz. Hangi bölümü önce yapalım: kullanıcı, bisiklet ekleme, yoksa kiralama işlemi mi?






You said:
kullaniciyi yapalim


ChatGPT said:
Harika, kullanıcı modülüyle başlayalım. Kullanıcılar, sistemde bisiklet kiralayan müşteriler olacak. Bu modülde şu işlemleri yapacağız:

✅ Kullanıcı İşlevleri
Kullanıcı ekleme

Kullanıcı listeleme

(Opsiyonel) Kullanıcı düzenleme/silme

🔧 1. Veritabanı (Zaten varsa geç)
Eğer daha önce oluşturmadıysan:

sql
Copy
Edit
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20)
);
📄 2. Kullanıcı Ekleme Formu (add_user.php)
php
Copy
Edit
<!-- add_user.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcı Ekle</title>
</head>
<body>
    <h2>Kullanıcı Ekle</h2>
    <form action="insert_user.php" method="POST">
        <label>Ad Soyad:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Telefon:</label><br>
        <input type="text" name="phone" required><br><br>

        <input type="submit" value="Kaydet">
    </form>
</body>
</html>
🧠 3. Veritabanına Kullanıcı Kaydetme (insert_user.php)
php
Copy
Edit
<?php
require 'connect.php';

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];

$stmt = $pdo->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $phone]);

echo "Kullanıcı başarıyla eklendi. <a href='add_user.php'>Yeni kullanıcı ekle</a> | <a href='list_users.php'>Kullanıcıları görüntüle</a>";
?>
📋 4. Kullanıcıları Listeleme (list_users.php)
php
Copy
Edit
<?php
require 'connect.php';

$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kullanıcılar</title>
</head>
<body>
    <h2>Kullanıcı Listesi</h2>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Ad Soyad</th>
            <th>Email</th>
            <th>Telefon</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
🔗 5. Bağlantı Dosyası (connect.php)
Eğer önceki mesajdan almadıysan:

php
Copy
Edit
<?php
$pdo = new PDO("mysql:host=localhost;dbname=bikerental;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
🎉 Artık kullanıcıları:
add_user.php üzerinden ekleyebilirsin.

list_users.php üzerinden görebilirsin.

➕ Sonraki Adım?
Hazırsan "bisiklet ekleme ve listeleme" modülünü yapabiliriz. Devam edelim mi?






You said:
devam


ChatGPT said:
Harika! Şimdi bisiklet ekleme ve listeleme modülünü yapalım. Bu modül, sistemdeki tüm bisikletleri görmemizi ve yeni bisikletler eklememizi sağlar.

🚲 1. Veritabanı Tablosu (bikes)
Eğer daha önce oluşturmadıysan:

sql
Copy
Edit
CREATE TABLE bikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    model VARCHAR(100),
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    price_per_hour DECIMAL(6,2)
);
📄 2. Bisiklet Ekleme Formu (add_bike.php)
php
Copy
Edit
<!-- add_bike.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Bisiklet Ekle</title>
</head>
<body>
    <h2>Bisiklet Ekle</h2>
    <form action="insert_bike.php" method="POST">
        <label>Model:</label><br>
        <input type="text" name="model" required><br><br>

        <label>Saatlik Ücret (₺):</label><br>
        <input type="number" name="price_per_hour" step="0.01" required><br><br>

        <input type="submit" value="Kaydet">
    </form>
</body>
</html>
🧠 3. Veritabanına Bisiklet Kaydetme (insert_bike.php)
php
Copy
Edit
<?php
require 'connect.php';

$model = $_POST['model'];
$price_per_hour = $_POST['price_per_hour'];

// Varsayılan olarak "available" (müsait) olarak ekleniyor
$stmt = $pdo->prepare("INSERT INTO bikes (model, price_per_hour) VALUES (?, ?)");
$stmt->execute([$model, $price_per_hour]);

echo "Bisiklet başarıyla eklendi. <a href='add_bike.php'>Yeni bisiklet ekle</a> | <a href='list_bikes.php'>Bisikletleri görüntüle</a>";
?>
📋 4. Bisiklet Listeleme Sayfası (list_bikes.php)
php
Copy
Edit
<?php
require 'connect.php';

$stmt = $pdo->query("SELECT * FROM bikes");
$bikes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bisiklet Listesi</title>
</head>
<body>
    <h2>Bisiklet Listesi</h2>
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Model</th>
            <th>Durum</th>
            <th>Saatlik Ücret (₺)</th>
        </tr>
        <?php foreach ($bikes as $bike): ?>
            <tr>
                <td><?= $bike['id'] ?></td>
                <td><?= htmlspecialchars($bike['model']) ?></td>
                <td><?= $bike['status'] ?></td>
                <td><?= number_format($bike['price_per_hour'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
🔗 Bağlantı Dosyası
Daha önceki adımlarda connect.php'yi zaten kullanmıştık. Aynı şekilde çalışır.

✅ Bu aşamada neler yapabiliyorsun?
add_bike.php: Yeni bisiklet ekleme

list_bikes.php: Tüm bisikletleri listeleme ve durumlarını görme

🚀 Sonraki adım:
Şimdi kiralama işlemini yapmaya başlayabiliriz:

Kullanıcı ve bisiklet seç

Kiralama başlat (zaman kaydet)

Durumları güncelle

Hazırsan, "kiralama başlatma" kısmına geçelim mi?
