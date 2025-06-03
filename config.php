<?php

// เพิ่มที่ต้นไฟล์ config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// **แก้ไข Encoding ภาษาไทย**
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

/**
 * AI Prompt Generator Pro - Database Configuration
 * ไฟล์กำหนดค่าการเชื่อมต่อฐานข้อมูลและฟังก์ชันพื้นฐาน
 */

// การตั้งค่าฐานข้อมูล
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'xxvdoxxc_ruaysabai'); // From user-provided config.php
define('DB_PASSWORD', '0804441958');       // From user-provided config.php
define('DB_DATABASE', 'xxvdoxxc_ruaysabai'); // From user-provided config.php

// การตั้งค่าระบบ
define('ADMIN_PASSWORD', 'admin123'); // From user-provided config.php
define('SITE_TITLE', 'AI Prompt Generator Pro'); // From user-provided config.php
define('SITE_URL', 'https://ruaysabai.com'); // แก้ไขเป็น URL หลักของเว็บคุณ (ไม่มี /admin.php ต่อท้าย)

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok'); // From user-provided config.php

// เปิด session
if (session_status() == PHP_SESSION_NONE) { // From user-provided config.php
    session_start(); // From user-provided config.php
}

/**
 * คลาสจัดการฐานข้อมูล (เวอร์ชันล่าสุดที่รองรับ Prepared Statements และ NULL)
 */
class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $this->connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
            if ($this->connection->connect_error) { // From user-provided config.php
                throw new Exception('Connection failed: ' . $this->connection->connect_error); // From user-provided config.php
            }
            $this->connection->set_charset("utf8mb4"); // From user-provided config.php
            $this->connection->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"); // From user-provided config.php
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage()); // From user-provided config.php
            die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()); // From user-provided config.php
        }
    }

    public static function getInstance() { if (self::$instance == null) { self::$instance = new Database(); } return self::$instance; } // From user-provided config.php
    public function getConnection() { return $this->connection; } // From user-provided config.php
    public function escape($value) { return $this->connection->real_escape_string($value); } // From user-provided config.php

    public function query($sql) { // ใช้สำหรับ CREATE TABLE หรือ query ที่ไม่มี user input โดยตรง
        $result = $this->connection->query($sql);
        if (!$result && $this->connection->error) {
            error_log('Query error: ' . $this->connection->error . ' SQL: ' . $sql); // From user-provided config.php
            return false;
        }
        return $result;
    }
    
    public function insert($table, $data) {
        if (empty($data)) return false;
        $columns_arr = array_keys($data);
        $columns = "`" . implode('`,`', $columns_arr) . "`";
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        
        $values = array_values($data);
        $types = "";
        foreach ($values as $value) {
            if ($value === null) $types .= "s"; 
            elseif (is_int($value)) $types .= "i";
            elseif (is_double($value)) $types .= "d";
            else $types .= "s";
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            error_log("Insert Prepare failed for table {$table}: (" . $this->connection->errno . ") " . $this->connection->error . " SQL: " . $sql);
            return false;
        }
        
        $bind_params = [];
        foreach ($values as $key => &$val_ref) { 
            $bind_params[$key] = &$val_ref;
        }

        if (!empty($types) && !empty($bind_params)) { 
           $stmt->bind_param($types, ...$bind_params);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Insert Execute failed for table {$table}: (" . $stmt->errno . ") " . $stmt->error . " SQL: ". $sql . " PARAMS: " . json_encode($values));
            $stmt->close();
            return false;
        }
    }

    public function update($table, $data, $where_clause, $where_params = []) {
        if (empty($data)) return false; $set_parts = []; $values = []; $types = "";
        foreach ($data as $key => $value) { $set_parts[] = "`{$key}` = ?"; $values[] = $value; if ($value === null) $types .= "s"; elseif (is_int($value)) $types .= "i"; elseif (is_double($value)) $types .= "d"; else $types .= "s"; }
        $set_string = implode(', ', $set_parts);
        foreach ($where_params as $param) { $values[] = $param; if ($param === null) $types .= "s"; elseif (is_int($param)) $types .= "i"; elseif (is_double($param)) $types .= "d"; else $types .= "s"; }
        $sql = "UPDATE `{$table}` SET {$set_string} WHERE {$where_clause}";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) { error_log("Update Prepare failed for table {$table}: (" . $this->connection->errno . ") " . $this->connection->error . " SQL: " . $sql); return false; }
        $bind_params = []; foreach ($values as $key => &$val_ref) { $bind_params[$key] = &$val_ref; }
        if (!empty($types) && !empty($bind_params)) { $stmt->bind_param($types, ...$bind_params); }
        if ($stmt->execute()) { $affected_rows = $stmt->affected_rows; $stmt->close(); return $affected_rows; } 
        else { error_log("Update Execute failed for table {$table}: (" . $stmt->errno . ") " . $stmt->error. " SQL: ". $sql . " PARAMS: " . json_encode($values)); $stmt->close(); return false; }
    }

    public function delete($table, $where_clause, $where_params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where_clause}"; $values = $where_params; $types = "";
        foreach ($values as $value) { if ($value === null) $types .= "s"; elseif (is_int($value)) $types .= "i"; elseif (is_double($value)) $types .= "d"; else $types .= "s"; }
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) { error_log("Delete Prepare failed for table {$table}: (" . $this->connection->errno . ") " . $this->connection->error . " SQL: " . $sql); return false; }
        $bind_params = []; foreach ($values as $key => &$val_ref) { $bind_params[$key] = &$val_ref; }
        if (!empty($types) && !empty($bind_params)) { $stmt->bind_param($types, ...$bind_params); }
        if ($stmt->execute()) { $affected_rows = $stmt->affected_rows; $stmt->close(); return $affected_rows; } 
        else { error_log("Delete Execute failed for table {$table}: (" . $stmt->errno . ") " . $stmt->error. " SQL: ". $sql . " PARAMS: " . json_encode($values)); $stmt->close(); return false; }
    }

    public function select($sql, $params = []) {
        try {
            if (!empty($params)) {
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) { error_log('Prepare failed: (' . $this->connection->errno . ') ' . $this->connection->error . ' SQL: ' . $sql); return []; }
                $types = "";
                if (!empty($params)){
                    foreach ($params as $param) { if ($param === null) $types .= "s"; elseif (is_int($param)) $types .= "i"; elseif (is_double($param)) $types .= "d"; else $types .= "s"; }
                    $bind_params_select = []; foreach ($params as $key_select => &$val_select_ref) { $bind_params_select[$key_select] = &$val_select_ref; }
                    if (!empty($types)) $stmt->bind_param($types, ...$bind_params_select);
                }
                if (!$stmt->execute()) { error_log('Execute failed: (' . $stmt->errno . ') ' . $stmt->error . ' SQL: ' . $sql . ' PARAMS: ' . json_encode($params)); $stmt->close(); return []; }
                $result = $stmt->get_result(); $data = []; if ($result) { while ($row = $result->fetch_assoc()) { $data[] = $row; } }
                $stmt->close(); return $data;
            } else {
                $result = $this->connection->query($sql);
                if (!$result && $this->connection->error) { error_log('Query (non-prepared) error: ' . $this->connection->error . ' SQL: ' . $sql); return []; }
                $data = []; if ($result && $result->num_rows > 0) { while ($row = $result->fetch_assoc()) { $data[] = $row; } } return $data;
            }
        } catch (Exception $e) { error_log('Select error: ' . $e->getMessage()); }
        return [];
    }
    public function getLastInsertId() { return $this->connection->insert_id; } // From user-provided config.php
    public function close() { if ($this->connection) { $this->connection->close(); } } // From user-provided config.php
}

class PromptManager {
    public $db; 
    public function __construct() { $this->db = Database::getInstance(); } // From user-provided config.php
    
    public function createTables() {
        // ตาราง users: เปลี่ยน points_balance เป็น balance DECIMAL(10,2)
        $sql_users = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE NOT NULL, email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, avatar_url VARCHAR(255) DEFAULT NULL,
            user_type ENUM('admin','premium','free') DEFAULT 'free', member_type ENUM('free','monthly','yearly') DEFAULT 'free',
            expire_date DATE DEFAULT NULL, credits INT DEFAULT 10, daily_limit INT DEFAULT 10,
            balance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ยอดเงินคงเหลือ (บาท)', 
            last_reset_date DATE DEFAULT NULL, email_verified TINYINT(1) DEFAULT 0, verification_token VARCHAR(100) DEFAULT NULL,
            reset_token VARCHAR(100) DEFAULT NULL, reset_token_expires DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL,
            status ENUM('active','suspended','deleted') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql_users);

        // ตรวจสอบและแก้ไขคอลัมน์ points_balance หรือ balance
        $check_column_sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND (COLUMN_NAME = 'points_balance' OR COLUMN_NAME = 'balance')";
        $column_info = $this->db->select($check_column_sql, [DB_DATABASE]);
        if (empty($column_info)) {
            $alter_users_sql = "ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ยอดเงินคงเหลือ (บาท)' AFTER daily_limit";
            $this->db->query($alter_users_sql);
        } elseif ($column_info[0]['COLUMN_NAME'] === 'points_balance') { // ถ้ายังเป็นชื่อเดิม
            $alter_users_sql = "ALTER TABLE users CHANGE COLUMN points_balance balance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ยอดเงินคงเหลือ (บาท)'";
            $this->db->query($alter_users_sql);
        } elseif ($column_info[0]['COLUMN_NAME'] === 'balance' && $column_info[0]['DATA_TYPE'] !== 'decimal') { // ถ้าชื่อถูกแล้วแต่ type ผิด
             $alter_users_sql = "ALTER TABLE users MODIFY COLUMN balance DECIMAL(10,2) DEFAULT 0.00 COMMENT 'ยอดเงินคงเหลือ (บาท)'";
            $this->db->query($alter_users_sql);
        }


        $sql_examples = "CREATE TABLE IF NOT EXISTS prompt_examples ( id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, prompt TEXT NOT NULL, icon VARCHAR(100) DEFAULT 'fas fa-image', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_examples);
        $sql_gallery = "CREATE TABLE IF NOT EXISTS gallery_items ( id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, description TEXT, image_url TEXT NOT NULL, prompt TEXT NOT NULL, icon VARCHAR(100) DEFAULT 'fas fa-image', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_gallery);
        $sql_settings = "CREATE TABLE IF NOT EXISTS site_settings ( id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) UNIQUE NOT NULL, setting_value TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_settings);
        $sql_user_prompts = "CREATE TABLE IF NOT EXISTS user_prompts ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT DEFAULT NULL, subject VARCHAR(255), content_type VARCHAR(100), style VARCHAR(100), scene VARCHAR(255), details TEXT, generated_prompt TEXT NOT NULL, ip_address VARCHAR(45), user_agent TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_user_prompts);
        $sql_guest_usage = "CREATE TABLE IF NOT EXISTS guest_prompt_usage ( id INT AUTO_INCREMENT PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, prompt_generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, user_agent TEXT, INDEX idx_ip_date (ip_address, prompt_generated_at) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_guest_usage);
        
        // ตาราง Marketplace: เปลี่ยน price_points เป็น price DECIMAL(10,2)
        $sql_sellable_prompts = "CREATE TABLE IF NOT EXISTS sellable_prompts ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, image_url VARCHAR(255) DEFAULT NULL, actual_prompt TEXT NOT NULL, price DECIMAL(10,2) NOT NULL COMMENT 'ราคาเป็นเงินบาท', tags VARCHAR(255) DEFAULT NULL, status ENUM('pending_approval', 'approved', 'rejected', 'hidden_by_user', 'admin_removed') DEFAULT 'pending_approval', total_sales INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, approved_by INT DEFAULT NULL, approved_at TIMESTAMP NULL DEFAULT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql_sellable_prompts);
        
        // ตาราง Prompt Transactions: เปลี่ยนคอลัมน์ที่เกี่ยวกับ points เป็น amount (DECIMAL)
        $sql_prompt_transactions = "CREATE TABLE IF NOT EXISTS prompt_transactions ( id INT AUTO_INCREMENT PRIMARY KEY, sellable_prompt_id INT NOT NULL, buyer_user_id INT NOT NULL, seller_user_id INT NOT NULL, amount_transferred DECIMAL(10,2) NOT NULL COMMENT 'ยอดเงินที่จ่าย (บาท)', commission_rate DECIMAL(5,2) NOT NULL COMMENT '% ค่าคอม', commission_amount DECIMAL(10,2) NOT NULL COMMENT 'ค่าคอมมิชชั่น (บาท)', seller_earned_amount DECIMAL(10,2) NOT NULL COMMENT 'ยอดเงินที่ผู้ขายได้รับ (บาท)', transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (sellable_prompt_id) REFERENCES sellable_prompts(id) ON DELETE RESTRICT, FOREIGN KEY (buyer_user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (seller_user_id) REFERENCES users(id) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql_prompt_transactions);
        
        // ตาราง User Balance Topup: เปลี่ยนชื่อจาก user_points_topup และเปลี่ยน points_added เป็น amount_added
        $sql_user_balance_topup = "CREATE TABLE IF NOT EXISTS user_balance_topup ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, amount_added DECIMAL(10,2) NOT NULL COMMENT 'ยอดเงินที่เติม (บาท)', payment_method VARCHAR(50) DEFAULT NULL, transaction_ref VARCHAR(255) DEFAULT NULL, status ENUM('pending', 'completed', 'failed', 'admin_added') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, processed_by INT DEFAULT NULL, notes TEXT DEFAULT NULL, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql_user_balance_topup);
        
        $sql_user_purchased_prompts = "CREATE TABLE IF NOT EXISTS user_purchased_prompts ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, sellable_prompt_id INT NOT NULL, purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (sellable_prompt_id) REFERENCES sellable_prompts(id) ON DELETE CASCADE, UNIQUE KEY (user_id, sellable_prompt_id) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"; // From user-provided config.php
        $this->db->query($sql_user_purchased_prompts);

        // ตารางใหม่สำหรับคำขอถอนเงิน
        $sql_withdrawal_requests = "CREATE TABLE IF NOT EXISTS withdrawal_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL COMMENT 'ID ผู้ขายที่ขอถอนเงิน',
            amount DECIMAL(10,2) NOT NULL COMMENT 'จำนวนเงินที่ขอถอน (บาท)',
            bank_account_name VARCHAR(255) DEFAULT NULL,
            bank_account_number VARCHAR(50) DEFAULT NULL,
            bank_name VARCHAR(100) DEFAULT NULL,
            status ENUM('pending', 'processing', 'completed', 'rejected', 'cancelled_by_user') DEFAULT 'pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL DEFAULT NULL,
            processed_by INT DEFAULT NULL COMMENT 'Admin ID ที่ดำเนินการ',
            admin_notes TEXT DEFAULT NULL,
            transaction_slip_url VARCHAR(255) DEFAULT NULL COMMENT 'URL สลิปการโอน (ถ้ามี)',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->db->query($sql_withdrawal_requests);
        
        $this->insertDefaultData();
    }

    private function insertDefaultData() {
        try {
            $admin_exists = $this->db->select("SELECT COUNT(*) as count FROM users WHERE username = ?", ['admin']);
            if (empty($admin_exists) || $admin_exists[0]['count'] == 0) {
                $this->db->insert('users', [
                    'username' => 'admin', 'email' => 'admin@example.com', 'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'full_name' => 'ผู้ดูแลระบบ', 'user_type' => 'admin', 'member_type' => 'yearly', 'credits' => 999999, 'daily_limit' => 999999,
                    'balance' => 10000.00, // << ยอดเงินเริ่มต้น
                    'status' => 'active', 'expire_date' => null, 'avatar_url' => null, 'last_reset_date' => null,
                    'verification_token' => null, 'reset_token' => null, 'reset_token_expires' => null, 'last_login' => null
                ]);
            }
            
            // $test_users_data = [ ... ]; // อัปเดต test users ให้มี balance เป็น DECIMAL
            
            $defaultSettingsData = [
                'site_title' => 'AI Prompt Generator Pro', 'site_description' => 'สร้าง Prompt สำหรับภาพคมชัด สมจริง',
                'online_count' => '182', 'placeholder_title' => 'เริ่มสร้าง Prompt ของคุณ',
                'placeholder_description' => 'กรอกข้อมูลในฟอร์มและกดปุ่ม "สร้าง Prompt" เพื่อเริ่มต้น',
                'gallery_title' => 'แกลเลอรี่ Prompt', 'gallery_description' => 'เลือกดูตัวอย่างภาพและคัดลอก Prompt',
                'commission_rate' => '5', // 5%
                'default_prompt_price' => '10.00' // ราคาเริ่มต้น 10.00 บาท
            ];
            foreach ($defaultSettingsData as $key => $value) {
                $exists = $this->db->select("SELECT COUNT(*) as count FROM site_settings WHERE setting_key = ?", [$key]);
                if (empty($exists) || $exists[0]['count'] == 0) {
                    $this->db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
                }
            }
        } catch (Exception $e) { error_log('Error in insertDefaultData: ' . $e->getMessage()); }
    }

    public function getAllExamples() { return $this->db->select("SELECT * FROM prompt_examples ORDER BY id DESC") ?: []; } // From user-provided config.php
    public function getAllGalleryItems() { return $this->db->select("SELECT * FROM gallery_items ORDER BY id DESC") ?: []; } // From user-provided config.php
    public function getSetting($key, $default = null) { $result = $this->db->select("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]); return !empty($result) ? $result[0]['setting_value'] : $default; } // From user-provided config.php
    public function setSetting($key, $value) { $data = ['setting_value' => $value]; $where_clause = "setting_key = ?"; $where_params = [$key]; $existing = $this->db->select("SELECT id FROM site_settings WHERE setting_key = ?", [$key]); if (!empty($existing)) { return $this->db->update('site_settings', $data, $where_clause, $where_params); } else { return $this->db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]); } } // From user-provided config.php
    public function saveUserPrompt($data) { if (!isset($data['ip_address'])) { $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';} if (!isset($data['user_agent'])) { $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown_agent';} if (empty($data['user_id'])) { $data['user_id'] = null;} return $this->db->insert('user_prompts', $data); } // From user-provided config.php
    public function getStats() { /* ... (เหมือนเดิมที่คุณมีล่าสุด) ... */ return [];} // From user-provided config.php
    
    public function getUserBalance($userId) { $result = $this->db->select("SELECT balance FROM users WHERE id = ?", [$userId]); return !empty($result) ? (float)$result[0]['balance'] : 0.00; }
    public function getCommissionRate() { return (float) $this->getSetting('commission_rate', 5);  }
    
    public function submitSellablePrompt($userId, $title, $description, $imageUrl, $actualPrompt, $priceAmount, $tags = '') { 
        $data = [ 'user_id' => $userId, 'title' => $title, 'description' => $description, 'image_url' => $imageUrl, 'actual_prompt' => $actualPrompt, 'price' => (float)$priceAmount, 'tags' => $tags, 'status' => 'pending_approval' ]; 
        if ($this->db->insert('sellable_prompts', $data)) { return $this->db->getLastInsertId(); } return false; 
    }
    public function getSellablePrompts($limit = 10, $offset = 0, $filters = []) { /* ... (เหมือนเดิม) ... */ return [];}
    public function getTotalApprovedSellablePrompts($filters = []) { /* ... (เหมือนเดิม) ... */ return 0;}
    public function getSellablePromptById($promptId, $currentUserId = null) { 
        $sql = "SELECT sp.*, u.username as seller_username, u.avatar_url as seller_avatar 
                FROM sellable_prompts sp JOIN users u ON sp.user_id = u.id 
                WHERE sp.id = ? AND (sp.status = 'approved' OR sp.user_id = ?)";
        $prompt = $this->db->select($sql, [$promptId, $currentUserId ?? 0]); 
        if (empty($prompt)) return null; 
        $promptData = $prompt[0]; $promptData['is_owner'] = false; $promptData['is_purchased'] = false; 
        if ($currentUserId) { 
            if ($promptData['user_id'] == $currentUserId) { $promptData['is_owner'] = true; $promptData['is_purchased'] = true; } 
            else { $purchasedSql = "SELECT id FROM user_purchased_prompts WHERE user_id = ? AND sellable_prompt_id = ?"; 
            $isPurchased = $this->db->select($purchasedSql, [$currentUserId, $promptId]); 
            if (!empty($isPurchased)) { $promptData['is_purchased'] = true; } } 
        } 
        if (!$promptData['is_purchased']) { unset($promptData['actual_prompt']); } 
        return $promptData; 
    }
    
    public function purchasePrompt($buyerUserId, $promptId) { 
        $this->db->getConnection()->begin_transaction(); 
        try {
            $prompt_to_buy_res = $this->db->select("SELECT * FROM sellable_prompts WHERE id = ? AND status = 'approved' FOR UPDATE", [$promptId]);
            if (empty($prompt_to_buy_res)) { $this->db->getConnection()->rollback(); return ['success' => false, 'message' => 'ไม่พบ Prompt นี้']; }
            $prompt_to_buy = $prompt_to_buy_res[0];
            $price = (float)$prompt_to_buy['price'];
            $sellerId = (int)$prompt_to_buy['user_id'];
            if ($sellerId == $buyerUserId) { $this->db->getConnection()->rollback(); return ['success' => false, 'message' => 'คุณไม่สามารถซื้อ Prompt ของตัวเองได้']; }
            $already_purchased = $this->db->select("SELECT id FROM user_purchased_prompts WHERE user_id = ? AND sellable_prompt_id = ?", [$buyerUserId, $promptId]);
            if (!empty($already_purchased)) { $this->db->getConnection()->rollback(); return ['success' => false, 'message' => 'คุณได้ซื้อ Prompt นี้ไปแล้ว']; }
            $buyer_res = $this->db->select("SELECT balance FROM users WHERE id = ? FOR UPDATE", [$buyerUserId]);
            if (empty($buyer_res) || (float)$buyer_res[0]['balance'] < $price) { $this->db->getConnection()->rollback(); return ['success' => false, 'message' => 'ยอดเงินของคุณไม่เพียงพอ กรุณาเติมเงิน']; }
            
            $buyer_balance = (float)$buyer_res[0]['balance'];
            $commissionRateDecimal = $this->getCommissionRate() / 100; 
            $commissionAmount = round($price * $commissionRateDecimal, 2); 
            $sellerEarnedAmount = $price - $commissionAmount;

            if ($this->db->update('users', ['balance' => $buyer_balance - $price], "id = ?", [$buyerUserId]) === false) { throw new Exception("ล้มเหลวในการอัปเดตยอดเงินผู้ซื้อ"); }
            $seller_res = $this->db->select("SELECT balance FROM users WHERE id = ? FOR UPDATE", [$sellerId]);
            if(empty($seller_res)){ throw new Exception("ไม่พบข้อมูลผู้ขาย ID: {$sellerId}"); }
            $seller_balance = (float)$seller_res[0]['balance'];
            if ($this->db->update('users', ['balance' => $seller_balance + $sellerEarnedAmount], "id = ?", [$sellerId]) === false) { throw new Exception("ล้มเหลวในการอัปเดตยอดเงินผู้ขาย"); }
            
            $transactionData = [ 'sellable_prompt_id' => $promptId, 'buyer_user_id' => $buyerUserId, 'seller_user_id' => $sellerId, 'amount_transferred' => $price, 'commission_rate' => $this->getCommissionRate(), 'commission_amount' => $commissionAmount, 'seller_earned_amount' => $sellerEarnedAmount ];
            if (!$this->db->insert('prompt_transactions', $transactionData)) { throw new Exception("ล้มเหลวในการบันทึก Transaction"); }
            if (!$this->db->insert('user_purchased_prompts', ['user_id' => $buyerUserId, 'sellable_prompt_id' => $promptId])) { throw new Exception("ล้มเหลวในการบันทึก Prompt ที่ซื้อ"); }
            if ($this->db->update('sellable_prompts', ['total_sales' => (int)$prompt_to_buy['total_sales'] + 1], "id = ?", [$promptId]) === false) { throw new Exception("ล้มเหลวในการอัปเดตจำนวนการขาย"); }
            
            $this->db->getConnection()->commit();
            return ['success' => true, 'message' => 'ซื้อ Prompt สำเร็จ!'];
        } catch (Exception $e) { $this->db->getConnection()->rollback(); error_log("Purchase System Error: " . $e->getMessage()); return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]; }
    }
    public function getUserPurchasedPrompts($userId, $limit = 10, $offset = 0) { /* ... (เหมือนเดิม) ... */ return [];}
    public function getSellerPrompts($userId, $limit = 10, $offset = 0) { /* ... (เหมือนเดิม) ... */ return [];}
    
    public function addUserBalance($userId, $amountToAdd, $reason = 'Balance added', $adminId = null, $transaction_ref = null) {
        $user = $this->db->select("SELECT balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
        if (empty($user)) return false;
        $new_balance = (float)$user[0]['balance'] + (float)$amountToAdd;
        if ($this->db->update('users', ['balance' => $new_balance], "id = ?", [$userId]) !== false) {
            $topupData = [ 'user_id' => $userId, 'amount_added' => (float)$amountToAdd, 'status' => ($adminId ? 'admin_added' : 'completed'), 'notes' => $reason, 'processed_by' => $adminId, 'transaction_ref' => $transaction_ref ];
            $this->db->insert('user_balance_topup', $topupData);
            return true;
        }
        return false;
    }

    public function requestWithdrawal($userId, $amount, $bankAccountName, $bankAccountNumber, $bankName) {
        $currentBalance = $this->getUserBalance($userId);
        if ($amount <= 0) { return ['success' => false, 'message' => 'จำนวนเงินที่ขอถอนต้องมากกว่า 0']; }
        if ($amount > $currentBalance) { return ['success' => false, 'message' => 'ยอดเงินคงเหลือของคุณไม่เพียงพอสำหรับการถอนนี้'];}
        // คุณอาจจะต้องการเพิ่มเงื่อนไขขั้นต่ำในการถอน เช่น ขั้นต่ำ 100 บาท
        // if ($amount < MINIMUM_WITHDRAWAL_AMOUNT) { return ['success' => false, 'message' => 'ยอดถอนขั้นต่ำคือ X บาท']; }

        $data = [
            'user_id' => $userId, 'amount' => (float)$amount,
            'bank_account_name' => $bankAccountName, 'bank_account_number' => $bankAccountNumber,
            'bank_name' => $bankName, 'status' => 'pending'
        ];
        if ($this->db->insert('withdrawal_requests', $data)) {
            // พิจารณา: ลดยอดเงินของผู้ใช้ทันที หรือลดเมื่อ Admin อนุมัติ?
            // ถ้าลดทันที:
            // $this->db->update('users', ['balance' => $currentBalance - (float)$amount], "id = ?", [$userId]);
            return ['success' => true, 'message' => 'ส่งคำขอถอนเงินเรียบร้อยแล้ว โปรดรอการตรวจสอบและอนุมัติจากผู้ดูแลระบบ'];
        }
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการส่งคำขอถอนเงิน'];
    }

    public function getWithdrawalRequests($filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT wr.*, u.username, u.email FROM withdrawal_requests wr JOIN users u ON wr.user_id = u.id";
        $params = []; $where_conditions = [];
        if (!empty($filters['status']) && $filters['status'] !== 'all') { $where_conditions[] = "wr.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['user_id'])) { $where_conditions[] = "wr.user_id = ?"; $params[] = (int)$filters['user_id'];}
        if (!empty($where_conditions)) { $sql .= " WHERE " . implode(" AND ", $where_conditions); }
        $sql .= " ORDER BY wr.requested_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit; $params[] = (int)$offset;
        return $this->db->select($sql, $params);
    }
    public function getTotalWithdrawalRequests($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM withdrawal_requests wr";
        $params = []; $where_conditions = [];
        if (!empty($filters['status']) && $filters['status'] !== 'all') { $where_conditions[] = "wr.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['user_id'])) { $where_conditions[] = "wr.user_id = ?"; $params[] = (int)$filters['user_id'];}
        if (!empty($where_conditions)) { $sql .= " WHERE " . implode(" AND ", $where_conditions); }
        $result = $this->db->select($sql, $params);
        return !empty($result) ? (int)$result[0]['total'] : 0;
    }

    public function processWithdrawal($requestId, $adminId, $newStatus, $adminNotes = null, $slipUrl = null) {
        $request = $this->db->select("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'", [$requestId]);
        if (empty($request)) { return ['success' => false, 'message' => 'ไม่พบคำขอถอนเงิน หรือคำขอถูกดำเนินการไปแล้ว/ถูกยกเลิก']; }
        $requestData = $request[0];
        $userId = (int)$requestData['user_id'];
        $amountToWithdraw = (float)$requestData['amount'];

        $this->db->getConnection()->begin_transaction();
        try {
            if ($newStatus === 'completed') {
                $userData = $this->db->select("SELECT balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
                if (empty($userData) || (float)$userData[0]['balance'] < $amountToWithdraw) {
                    throw new Exception('ยอดเงินของผู้ใช้ไม่เพียงพอสำหรับการถอนนี้ (อาจมีการเปลี่ยนแปลงหลังจากยื่นคำขอ)');
                }
                $newBalance = (float)$userData[0]['balance'] - $amountToWithdraw;
                if ($this->db->update('users', ['balance' => $newBalance], "id = ?", [$userId]) === false) {
                    throw new Exception('ไม่สามารถอัปเดตยอดเงินคงเหลือของผู้ใช้ได้');
                }
            } elseif ($newStatus === 'rejected') {
                // ถ้าปฏิเสธ ไม่ต้องทำอะไรกับยอดเงินผู้ใช้ (เพราะยังไม่ได้หักตอนยื่นคำขอ)
                // ถ้ามีการ lock ยอดเงินไว้ก่อนหน้า ก็ต้องปลด lock ตรงนี้
            }

            $updateData = [
                'status' => $newStatus, 'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => $adminId, 'admin_notes' => $adminNotes,
                'transaction_slip_url' => $slipUrl
            ];
            if ($this->db->update('withdrawal_requests', $updateData, "id = ?", [$requestId]) === false) {
                 throw new Exception('ไม่สามารถอัปเดตสถานะคำขอถอนเงินได้');
            }
            $this->db->getConnection()->commit();
            return ['success' => true, 'message' => 'ดำเนินการคำขอถอนเงินเรียบร้อยแล้ว สถานะ: ' . $newStatus];
        } catch (Exception $e) {
            $this->db->getConnection()->rollback();
            error_log("Process Withdrawal Error for Request ID {$requestId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการดำเนินการ: ' . $e->getMessage()];
        }
    }
}

function jsonResponse($success, $message, $data = null) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); exit; }
function cleanInput($data) { if (is_array($data)) { return array_map('cleanInput', $data); } $data = trim($data); $data = stripslashes($data); $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); return $data; }
function isAdmin() { $user = getCurrentUser(); return ($user && $user['user_type'] === 'admin' && isset($_SESSION['admin_legacy_logged_in']) && $_SESSION['admin_legacy_logged_in'] === true); }
function adminLogin($password) { if ($password === ADMIN_PASSWORD) { $_SESSION['admin_legacy_logged_in'] = true; return true; } return false; }
function adminLogout() { unset($_SESSION['admin_legacy_logged_in']); }
function isUserLoggedIn() { if (session_status() === PHP_SESSION_NONE) { session_start(); } return isset($_SESSION['user_id']); }
function getCurrentUser() { if (!isUserLoggedIn()) { return null; } $db = Database::getInstance(); $user_id = $_SESSION['user_id']; $user = $db->select("SELECT * FROM users WHERE id = ?", [$user_id]); return !empty($user) ? $user[0] : null; }
function isValidImageUrl($url) { if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) { return false; } $path = parse_url($url, PHP_URL_PATH); $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']; return in_array($ext, $allowed_extensions); }
function writeLog($message, $type = 'INFO') { $logFile = __DIR__ . '/logs/app.log'; $logDir = dirname($logFile); if (!is_dir($logDir)) { if (!@mkdir($logDir, 0755, true) && !is_dir($logDir)) { error_log("Failed to create log directory: {$logDir}"); return; } } $timestamp = date('Y-m-d H:i:s'); $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL; if (false === @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX)) { error_log("Failed to write to log file: {$logFile}"); } }

function getPageData() {
    $promptManager = new PromptManager();
    $settings = [];
    $db_settings_query_result = $promptManager->db->select("SELECT setting_key, setting_value FROM site_settings");
    if (is_array($db_settings_query_result)) {
        foreach($db_settings_query_result as $row){ $settings[$row['setting_key']] = $row['setting_value']; }
    }
    $default_site_title = defined('SITE_TITLE') ? SITE_TITLE : 'AI Prompt Site';
    $default_site_description = 'สร้าง Prompt สำหรับภาพคมชัดด้วย AI';
    $default_online_count = '100';
    $default_placeholder_title = 'เริ่มสร้าง Prompt ของคุณ';
    $default_placeholder_description = 'กรอกข้อมูลในฟอร์มและกดปุ่ม "สร้าง Prompt"';
    $default_gallery_title = 'แกลเลอรี่ Prompt';
    $default_gallery_description = 'ตัวอย่าง Prompt และภาพ';
    $default_commission_rate = '5';
    $default_default_prompt_price = '10.00';

    return [
        'examples' => $promptManager->getAllExamples() ?: [], 
        'gallery' => $promptManager->getAllGalleryItems() ?: [], 
        'settings' => [
            'site_title' => $settings['site_title'] ?? $default_site_title,
            'site_description' => $settings['site_description'] ?? $default_site_description,
            'online_count' => $settings['online_count'] ?? $default_online_count,
            'placeholder_title' => $settings['placeholder_title'] ?? $default_placeholder_title,
            'placeholder_description' => $settings['placeholder_description'] ?? $default_placeholder_description,
            'gallery_title' => $settings['gallery_title'] ?? $default_gallery_title,
            'gallery_description' => $settings['gallery_description'] ?? $default_gallery_description,
            'commission_rate' => $settings['commission_rate'] ?? $default_commission_rate,
            'default_prompt_price' => $settings['default_prompt_price'] ?? $default_default_prompt_price
        ]
    ];
}

try {
    $promptManager = new PromptManager();
    $promptManager->createTables();
} catch (Exception $e) {
    writeLog('Error initializing database: ' . $e->getMessage(), 'FATAL');
    error_log('Config initialization error: ' . $e->getMessage());
}

// define('DEBUG_MODE', true); 
if (!defined('DEBUG_MODE') || DEBUG_MODE === false) {
    // error_reporting(0); // Comment out for debugging on live site
    // ini_set('display_errors', 0); // Comment out for debugging on live site
}
?>