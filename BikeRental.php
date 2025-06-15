<?php
class BikeRental {
    /** @var PDO */
    private $conn;
    
    /** @var string */
    private $table_name = "bike_rentals";
    
    /** @var string|null */
    private $error_message = null;

    /**
     * BikeRental constructor.
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Hata mesajını almak için method
     * @return string|null
     */
    public function getError() {
        return $this->error_message;
    }

    /**
     * Kullanıcıya ait kiralamaları getir
     * @param int $user_id Kullanıcı ID
     * @param int|null $limit Limit değeri
     * @return bool|PDOStatement
     */
    public function readByUser($user_id, $limit = null) {
        try {
            if (!is_numeric($user_id)) {
                $this->error_message = "Geçersiz kullanıcı ID'si.";
                return false;
            }

            $query = "SELECT * FROM " . $this->table_name . " 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC";
            
            if ($limit !== null) {
                if (!is_numeric($limit) || $limit <= 0) {
                    $this->error_message = "Geçersiz limit değeri.";
                    return false;
                }
                $query .= " LIMIT :limit";
            }            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }

    // Kiralama tamamla metodunda hata kontrolü eklendi
    public function complete($id, $end_date, $total_cost) {
        try {
            $query = "UPDATE " . $this->table_name . " SET end_date = :end_date, total_cost = :total_cost, status = 'completed' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':total_cost', $total_cost);
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }    // Update metodunda eksik olan validate kontrolü eklendi
    public function update($id, $bike_brand, $bike_model, $bike_color, $price_per_hour, $customer_name, $customer_phone, $customer_email, $customer_id_number, $start_date, $end_date, $status, $total_cost, $notes) {
        if (!$this->validateInput($bike_brand, $bike_model, $start_date, $end_date)) {
            return false;
        }

        try {
            $query = "UPDATE " . $this->table_name . " SET 
                    bike_brand = :bike_brand, bike_model = :bike_model, bike_color = :bike_color, 
                    price_per_hour = :price_per_hour, customer_name = :customer_name, customer_phone = :customer_phone, 
                    customer_email = :customer_email, customer_id_number = :customer_id_number, 
                    start_date = :start_date, end_date = :end_date, status = :status, 
                    total_cost = :total_cost, notes = :notes, 
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Tüm parametreleri bind et
            $params = [
                ':id' => $id,
                ':bike_brand' => $bike_brand,
                ':bike_model' => $bike_model,
                ':bike_color' => $bike_color,
                ':price_per_hour' => $price_per_hour,
                ':customer_name' => $customer_name,
                ':customer_phone' => $customer_phone,
                ':customer_email' => $customer_email,
                ':customer_id_number' => $customer_id_number,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':total_cost' => $total_cost,
                ':notes' => $notes
            ];

            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }    /**
     * Yeni kiralama kaydı oluşturur
     * @param int $user_id Kullanıcı ID
     * @param string $bike_brand Bisiklet markası
     * @param string $bike_model Bisiklet modeli
     * @param string $bike_color Bisiklet rengi
     * @param float $price_per_hour Saatlik ücret
     * @param string $customer_name Müşteri adı
     * @param string $customer_phone Müşteri telefonu
     * @param string $customer_email Müşteri e-postası
     * @param string $customer_id_number Müşteri kimlik no
     * @param string $start_date Başlangıç tarihi
     * @param string $end_date Bitiş tarihi
     * @param string $status Durum
     * @param float $total_cost Toplam ücret
     * @param string $notes Notlar
     * @return bool
     */
    public function create($user_id, $bike_brand, $bike_model, $bike_color, $price_per_hour, $customer_name, $customer_phone, $customer_email, $customer_id_number, $start_date, $end_date, $status, $total_cost, $notes) {
        if (!$this->validateInput($bike_brand, $bike_model, $start_date, $end_date)) {
            return false;
        }

        // Tarih formatını kontrol et
        if (!strtotime($start_date) || !strtotime($end_date)) {
            $this->error_message = "Geçersiz tarih formatı";
            return false;
        }

        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (user_id, bike_brand, bike_model, bike_color, price_per_hour, customer_name, 
                    customer_phone, customer_email, customer_id_number, start_date, end_date, 
                    status, total_cost, notes, created_at, updated_at) 
                    VALUES 
                    (:user_id, :bike_brand, :bike_model, :bike_color, :price_per_hour, :customer_name, 
                    :customer_phone, :customer_email, :customer_id_number, :start_date, :end_date, 
                    :status, :total_cost, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $stmt = $this->conn->prepare($query);
            
            // Tüm parametreleri bind et
            $params = [
                ':user_id' => $user_id,
                ':bike_brand' => $bike_brand,
                ':bike_model' => $bike_model,
                ':bike_color' => $bike_color,
                ':price_per_hour' => $price_per_hour,
                ':customer_name' => $customer_name,
                ':customer_phone' => $customer_phone,
                ':customer_email' => $customer_email,
                ':customer_id_number' => $customer_id_number,
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':status' => $status,
                ':total_cost' => $total_cost,
                ':notes' => $notes
            ];

            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }

    /**
     * Debug için log yazan yardımcı fonksiyon
     */
    private function debug($message, $data = null) {
        error_log("BikeRental Debug: " . $message);
        if ($data !== null) {
            error_log("Data: " . print_r($data, true));
        }
    }

    /**
     * Kullanıcının kiralamalarını sayfalı olarak getirir
     * @param int $user_id Kullanıcı ID
     * @param string $search Arama terimi
     * @param string $status_filter Durum filtresi
     * @param int $limit Sayfa başına kayıt sayısı
     * @param int $offset Başlangıç kaydı
     * @return array|false
     */
    public function getUserRentals($user_id, $search = '', $status_filter = '', $limit = 10, $offset = 0) {
        try {
            $this->debug("getUserRentals çağrıldı", [
                'user_id' => $user_id,
                'search' => $search,
                'status_filter' => $status_filter,
                'limit' => $limit,
                'offset' => $offset
            ]);

            if (!is_numeric($user_id) || $user_id <= 0) {
                $this->error_message = "Geçersiz kullanıcı ID'si";
                $this->debug("Geçersiz kullanıcı ID'si: " . $user_id);
                return false;
            }

            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
            $params = [':user_id' => $user_id];

            if (!empty($search)) {
                $query .= " AND (bike_brand LIKE :search OR bike_model LIKE :search OR 
                           customer_name LIKE :search OR customer_phone LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            if (!empty($status_filter)) {
                $query .= " AND status = :status";
                $params[':status'] = $status_filter;
            }

            $query .= " ORDER BY created_at DESC";
            
            if ($limit > 0) {
                $query .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = (int)$limit;
                $params[':offset'] = (int)$offset;
            }

            $this->debug("SQL Query", $query);
            $this->debug("Parameters", $params);

            $stmt = $this->conn->prepare($query);
            
            // PDO parametre bağlama
            foreach ($params as $key => $val) {
                if ($key == ':limit' || $key == ':offset') {
                    $stmt->bindValue($key, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $val);
                }
            }

            if (!$stmt->execute()) {
                $this->error_message = "Sorgu çalıştırılamadı";
                $this->debug("Sorgu çalıştırma hatası", $stmt->errorInfo());
                return false;
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $this->error_message = "Veriler alınamadı";
                $this->debug("fetchAll hatası", $stmt->errorInfo());
                return false;
            }
            
            $this->debug("Bulunan kayıt sayısı: " . count($result));
            return $result;

        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            $this->debug("PDO Exception", $e->getMessage());
            return false;
        }
    }

    /**
     * Kullanıcının toplam kiralama sayısını döndürür
     * @param int $user_id Kullanıcı ID
     * @param string $search Arama terimi
     * @param string $status_filter Durum filtresi
     * @return int|false
     */
    public function countUserRentals($user_id, $search = '', $status_filter = '') {
        try {
            $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE user_id = :user_id";
            $params = [':user_id' => $user_id];

            if (!empty($search)) {
                $query .= " AND (bike_brand LIKE :search OR bike_model LIKE :search OR 
                           customer_name LIKE :search OR customer_phone LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            if (!empty($status_filter)) {
                $query .= " AND status = :status";
                $params[':status'] = $status_filter;
            }

            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }

            if (!$stmt->execute()) {
                $this->error_message = "Sorgu çalıştırılamadı";
                return false;
            }

            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            $this->error_message = $e->getMessage();
            return false;
        }
    }

    /**
     * ID'ye göre kiralama kaydını getirir
     * @param int $id Kiralama ID'si
     * @return array|false
     */
    public function getById($id) {
        try {
            error_log("BikeRental Debug - getById called with ID: " . $id);

            if (!is_numeric($id) || $id <= 0) {
                $this->error_message = "Geçersiz kiralama ID'si";
                error_log("BikeRental Error - Invalid ID format: " . $id);
                return false;
            }

            // Önce ID'nin varlığını kontrol et
            $check_query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE id = :id";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->fetchColumn() == 0) {
                $this->error_message = "Kiralama kaydı bulunamadı (ID: " . $id . ")";
                error_log("BikeRental Error - Rental not found with ID: " . $id);
                return false;
            }

            $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
            error_log("BikeRental Debug - Query: " . $query);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->error_message = "Sorgu çalıştırılamadı";
                error_log("BikeRental Error - Query execution failed: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                $this->error_message = "Kiralama kaydı bulunamadı";
                error_log("BikeRental Error - No rental found after successful query");
                return false;
            }
            
            error_log("BikeRental Debug - Rental found: " . print_r($result, true));
            return $result;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            error_log("BikeRental Error - PDO Exception in getById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiralama kaydını siler
     * @param int $id Silinecek kiralamanın ID'si
     * @return bool İşlem başarılı ise true, değilse false
     */
    public function delete($id) {
        try {
            error_log("Delete operation started for rental ID: " . $id);
            
            // Önce kaydın var olduğunu kontrol et
            $current_rental = $this->getById($id);
            if (!$current_rental) {
                $this->error_message = "Silinecek kiralama kaydı bulunamadı.";
                error_log("Rental record not found for deletion. ID: " . $id);
                return false;
            }

            error_log("Current rental status: " . $current_rental['status']);

            // Özel durum: Aktif kiralama ise iptal et
            if ($current_rental['status'] === 'active') {
                error_log("Active rental detected, updating status to cancelled");
                
                // Önce durumu iptal olarak güncelle
                $update_query = "UPDATE " . $this->table_name . " 
                               SET status = 'cancelled', 
                                   updated_at = CURRENT_TIMESTAMP,
                                   notes = CONCAT(COALESCE(notes, ''), '\nKiralama iptal edildi ve silindi.')
                               WHERE id = :id";
                
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                
                if (!$update_stmt->execute()) {
                    $this->error_message = "Kiralama iptal edilemedi.";
                    error_log("Failed to update rental status to cancelled. Error: " . print_r($update_stmt->errorInfo(), true));
                    return false;
                }
            }

            // Şimdi kaydı sil
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                error_log("Rental successfully deleted. ID: " . $id);
                return true;
            }
            
            $this->error_message = "Silme işlemi sırasında bir hata oluştu.";
            error_log("Delete operation failed. Error: " . print_r($stmt->errorInfo(), true));
            return false;

        } catch (PDOException $e) {
            $this->error_message = "Veritabanı hatası: " . $e->getMessage();
            error_log("Database error during delete operation: " . $e->getMessage());
            return false;
        }
    }

    // Input validation için yeni method
    private function validateInput($bike_brand, $bike_model, $start_date, $end_date) {
        // Temel validasyonlar
        if (empty($bike_brand) || empty($bike_model)) {
            $this->error_message = "Bisiklet marka ve model bilgileri gereklidir.";
            return false;
        }

        // Başlangıç tarihi kontrolü
        if (!empty($start_date)) {
            $start_timestamp = strtotime($start_date);
            if ($start_timestamp === false) {
                $this->error_message = "Geçersiz başlangıç tarihi formatı.";
                return false;
            }
        }

        // Bitiş tarihi kontrolü - Kiralama tamamlanırken
        if (!empty($end_date)) {
            $end_timestamp = strtotime($end_date);
            if ($end_timestamp === false) {
                $this->error_message = "Geçersiz bitiş tarihi formatı.";
                return false;
            }

            // Eğer her iki tarih de varsa karşılaştır
            if (!empty($start_date) && !empty($end_date)) {
                $start_timestamp = strtotime($start_date);
                $end_timestamp = strtotime($end_date);

                // Erken tamamlama kontrolü - gelecekteki kiralamaları şu anki zamanda tamamlamaya izin ver
                $current_time = time();
                if ($end_timestamp < $current_time) {
                    // Bitiş zamanı geçmişte, start_date'i kontrol et
                    if ($start_timestamp > $current_time) {
                        // Başlangıç gelecekte ama bitiş geçmişte - erken tamamlama
                        $start_timestamp = $current_time;
                    }
                }

                if ($end_timestamp < $start_timestamp && $end_timestamp != $current_time) {
                    $this->error_message = "Bitiş tarihi başlangıç tarihinden önce olamaz.";
                    error_log("Tarih karşılaştırma hatası - Başlangıç: " . date('Y-m-d H:i:s', $start_timestamp) . 
                             ", Bitiş: " . date('Y-m-d H:i:s', $end_timestamp) . 
                             ", Şu an: " . date('Y-m-d H:i:s', $current_time));
                    return false;
                }
            }
        }

        return true;
    }
}
?>