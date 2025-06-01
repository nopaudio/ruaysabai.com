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
define('DB_USERNAME', 'xxvdoxxc_ruaysabai');
define('DB_PASSWORD', '0804441958');
define('DB_DATABASE', 'xxvdoxxc_ruaysabai');

// การตั้งค่าระบบ
define('ADMIN_PASSWORD', 'admin123');
define('SITE_TITLE', 'AI Prompt Generator Pro');
define('SITE_URL', 'https://ruaysabai.com/admin.php');

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');

// เปิด session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * คลาสจัดการฐานข้อมูล
 */
class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
            
            if ($this->connection->connect_error) {
                throw new Exception('Connection failed: ' . $this->connection->connect_error);
            }
            
            // **ตั้งค่า charset เป็น UTF-8 สำหรับภาษาไทย**
            $this->connection->set_charset("utf8mb4");
            
            // **เพิ่มคำสั่ง SQL สำหรับ UTF-8**
            $this->connection->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            
        } catch (Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * ป้องกัน SQL Injection
     */
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    // เพิ่มฟังก์ชัน escape_string สำหรับ register.php
    public function escape_string($value) {
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * เรียกใช้ Query
     */
    public function query($sql) {
        $result = $this->connection->query($sql);
        if (!$result && $this->connection->error) {
            error_log('Query error: ' . $this->connection->error . ' SQL: ' . $sql);
            return false;
        }
        return $result;
    }
    
    /**
     * Insert ข้อมูล
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $values = "'" . implode("','", array_map([$this, 'escape'], array_values($data))) . "'";
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        return $this->query($sql);
    }
    
    /**
     * Update ข้อมูล
     */
    public function update($table, $data, $where) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = '" . $this->escape($value) . "'";
        }
        $set = implode(',', $set);
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return $this->query($sql);
    }
    
    /**
     * Delete ข้อมูล
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql);
    }
    
    /**
     * Select ข้อมูล - แก้ไขให้รองรับ prepared statements
     */
    public function select($sql, $params = []) {
        try {
            if (!empty($params)) {
                // ใช้ prepared statement
                $stmt = $this->connection->prepare($sql);
                if (!$stmt) {
                    error_log('Prepare failed: ' . $this->connection->error);
                    return [];
                }
                
                if (count($params) > 0) {
                    $types = str_repeat('s', count($params)); // ถือว่าเป็น string ทั้งหมด
                    $stmt->bind_param($types, ...$params);
                }
                
                if (!$stmt->execute()) {
                    error_log('Execute failed: ' . $stmt->error);
                    $stmt->close();
                    return [];
                }
                
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                    $stmt->close();
                    return $data;
                }
                $stmt->close();
            } else {
                // ใช้ query ธรรมดา
                $result = $this->query($sql);
                if ($result && $result->num_rows > 0) {
                    $data = [];
                    while ($row = $result->fetch_assoc()) {
                        $data[] = $row;
                    }
                    return $data;
                }
            }
        } catch (Exception $e) {
            error_log('Select error: ' . $e->getMessage());
        }
        return [];
    }
    
    /**
     * ปิดการเชื่อมต่อ
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

/**
 * คลาสจัดการ Prompt Examples
 */
class PromptManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * สร้างตารางหากยังไม่มี
     */
    public function createTables() {
        // สร้างตาราง users ก่อน
        $sql_users = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            avatar_url VARCHAR(255) DEFAULT NULL,
            user_type ENUM('admin','premium','free') DEFAULT 'free',
            member_type ENUM('free','monthly','yearly') DEFAULT 'free',
            expire_date DATE DEFAULT NULL,
            credits INT DEFAULT 10,
            daily_limit INT DEFAULT 10,
            last_reset_date DATE DEFAULT NULL,
            email_verified TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(100) DEFAULT NULL,
            reset_token VARCHAR(100) DEFAULT NULL,
            reset_token_expires DATETIME DEFAULT NULL,
            last_login DATETIME DEFAULT NULL,
            status ENUM('active','suspended','deleted') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // ตาราง prompt_examples
        $sql_examples = "CREATE TABLE IF NOT EXISTS prompt_examples (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            prompt TEXT NOT NULL,
            icon VARCHAR(100) DEFAULT 'fas fa-image',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // ตาราง gallery_items
        $sql_gallery = "CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url TEXT NOT NULL,
            prompt TEXT NOT NULL,
            icon VARCHAR(100) DEFAULT 'fas fa-image',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // ตาราง site_settings
        $sql_settings = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // ตาราง user_prompts - แก้ไขให้มี ip_address และ user_agent
        $sql_user_prompts = "CREATE TABLE IF NOT EXISTS user_prompts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            subject VARCHAR(255),
            content_type VARCHAR(100),
            style VARCHAR(100),
            scene VARCHAR(255),
            details TEXT,
            generated_prompt TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        // Execute ทีละคำสั่ง และตรวจสอบผลลัพธ์
        $tables = [
            'users' => $sql_users,
            'prompt_examples' => $sql_examples,
            'gallery_items' => $sql_gallery,
            'site_settings' => $sql_settings,
            'user_prompts' => $sql_user_prompts
        ];
        
        foreach ($tables as $tableName => $sql) {
            $result = $this->db->query($sql);
            if (!$result) {
                error_log("Failed to create table {$tableName}");
            }
        }
        
        // เพิ่มข้อมูลเริ่มต้น
        $this->insertDefaultData();
    }
    
    /**
     * เพิ่มข้อมูลเริ่มต้น
     */
    private function insertDefaultData() {
        try {
            // สร้าง admin user
            $admin_exists = $this->db->select("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            if (empty($admin_exists) || $admin_exists[0]['count'] == 0) {
                $this->db->insert('users', [
                    'username' => 'admin',
                    'email' => 'admin@example.com',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'full_name' => 'ผู้ดูแลระบบ',
                    'user_type' => 'admin',
                    'member_type' => 'yearly',
                    'credits' => 999999,
                    'daily_limit' => 999999,
                    'status' => 'active'
                ]);
            }
            
            // สร้าง user ทดสอบแต่ละประเภท
            $test_users = [
                [
                    'username' => 'testfree',
                    'email' => 'testfree@example.com',
                    'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
                    'full_name' => 'ทดสอบ สมาชิกฟรี',
                    'user_type' => 'free',
                    'member_type' => 'free',
                    'credits' => 10,
                    'daily_limit' => 10,
                    'status' => 'active'
                ],
                [
                    'username' => 'testmonthly',
                    'email' => 'testmonthly@example.com',
                    'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
                    'full_name' => 'ทดสอบ สมาชิกรายเดือน',
                    'user_type' => 'premium',
                    'member_type' => 'monthly',
                    'credits' => 60,
                    'daily_limit' => 60,
                    'expire_date' => date('Y-m-d', strtotime('+1 month')),
                    'status' => 'active'
                ],
                [
                    'username' => 'testyearly',
                    'email' => 'testyearly@example.com',
                    'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
                    'full_name' => 'ทดสอบ สมาชิกรายปี',
                    'user_type' => 'premium',
                    'member_type' => 'yearly',
                    'credits' => 999999,
                    'daily_limit' => 999999,
                    'expire_date' => date('Y-m-d', strtotime('+1 year')),
                    'status' => 'active'
                ]
            ];
            
            foreach ($test_users as $test_user) {
                $exists = $this->db->select("SELECT COUNT(*) as count FROM users WHERE username = '{$test_user['username']}'");
                if (empty($exists) || $exists[0]['count'] == 0) {
                    $this->db->insert('users', $test_user);
                }
            }
            
            // ตรวจสอบว่ามีข้อมูล examples อยู่แล้วหรือไม่
            $existing = $this->db->select("SELECT COUNT(*) as count FROM prompt_examples");
            if (empty($existing) || $existing[0]['count'] == 0) {
                $defaultExamples = [
                    [
                        'title' => 'สาวสวยในสวนดอกไม้',
                        'prompt' => 'beautiful young woman, elegant portrait style, in blooming flower garden, gentle smile, wearing flowing white dress, soft pastel colors, golden hour lighting, dreamy atmosphere, medium shot angle, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-image'
                    ],
                    [
                        'title' => 'ห้องนั่งเล่นโมเดิร์น',
                        'prompt' => 'modern luxury living room, contemporary interior design style, spacious open-plan layout, minimalist furniture, neutral beige and white colors, natural afternoon sunlight, sophisticated atmosphere, wide angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-home'
                    ],
                    [
                        'title' => 'รถสปอร์ตแห่งอนาคต',
                        'prompt' => 'futuristic sports car, sleek automotive design style, on neon-lit city street, aerodynamic curves, metallic silver and electric blue accents, night with dramatic city lighting, high-tech atmosphere, low angle dynamic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-car'
                    ]
                ];
                
                foreach ($defaultExamples as $example) {
                    $this->db->insert('prompt_examples', $example);
                }
            }
            
            // gallery items
            $existing_gallery = $this->db->select("SELECT COUNT(*) as count FROM gallery_items");
            if (empty($existing_gallery) || $existing_gallery[0]['count'] == 0) {
                $defaultGallery = [
                    [
                        'title' => 'AI Robot Portrait',
                        'description' => 'หุ่นยนต์ AI แบบไซเบอร์พังค์',
                        'image_url' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=400&h=300&fit=crop&crop=center',
                        'prompt' => 'futuristic AI robot, cyberpunk portrait style, glowing blue eyes, metallic chrome finish, in dark tech laboratory, advanced circuitry details, neon blue and purple lighting, mysterious atmosphere, close-up shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-robot'
                    ],
                    [
                        'title' => 'Sunset Mountain',
                        'description' => 'ภูเขาในแสงพระอาทิตย์ตก',
                        'image_url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&h=300&fit=crop&crop=center',
                        'prompt' => 'majestic mountain landscape, dramatic sunset photography style, golden hour lighting, layered mountain silhouettes, vibrant orange and purple sky, misty valleys, serene peaceful atmosphere, wide panoramic shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-mountain'
                    ],
                    [
                        'title' => 'Luxury Sports Car',
                        'description' => 'รถสปอร์ตหรู ในสตูดิโอ',
                        'image_url' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=400&h=300&fit=crop&crop=center',
                        'prompt' => 'luxury sports car, automotive photography style, sleek metallic paint finish, dramatic studio lighting, reflective black floor, modern showroom background, silver and chrome accents, sophisticated atmosphere, low angle shot, masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting',
                        'icon' => 'fas fa-car'
                    ]
                ];
                
                foreach ($defaultGallery as $item) {
                    $this->db->insert('gallery_items', $item);
                }
            }
            
            // settings
            $existing_settings = $this->db->select("SELECT COUNT(*) as count FROM site_settings");
            if (empty($existing_settings) || $existing_settings[0]['count'] == 0) {
                $defaultSettings = [
                    ['setting_key' => 'site_title', 'setting_value' => 'AI Prompt Generator Pro'],
                    ['setting_key' => 'site_description', 'setting_value' => 'สร้าง Prompt สำหรับภาพคมชัด สมจริง ด้วยปัญญาประดิษฐ์ขั้นสูง'],
                    ['setting_key' => 'online_count', 'setting_value' => '182'],
                    ['setting_key' => 'placeholder_title', 'setting_value' => 'เริ่มสร้าง Prompt ของคุณ'],
                    ['setting_key' => 'placeholder_description', 'setting_value' => 'กรอกข้อมูลในฟอร์มและกดปุ่ม "สร้าง Prompt" เพื่อเริ่มต้น'],
                    ['setting_key' => 'gallery_title', 'setting_value' => 'แกลเลอรี่ Prompt พร้อมตัวอย่างภาพ'],
                    ['setting_key' => 'gallery_description', 'setting_value' => 'เลือกดูตัวอย่างภาพและคัดลอก Prompt ไปใช้งานได้ทันที']
                ];
                
                foreach ($defaultSettings as $setting) {
                    $this->db->insert('site_settings', $setting);
                }
            }
        } catch (Exception $e) {
            error_log('Error in insertDefaultData: ' . $e->getMessage());
        }
    }
    
    /**
     * ดึงข้อมูล prompt examples ทั้งหมด
     */
    public function getAllExamples() {
        return $this->db->select("SELECT * FROM prompt_examples ORDER BY id DESC");
    }
    
    /**
     * ดึงข้อมูล gallery items ทั้งหมด
     */
    public function getAllGalleryItems() {
        return $this->db->select("SELECT * FROM gallery_items ORDER BY id DESC");
    }
    
    /**
     * ดึงการตั้งค่า
     */
    public function getSetting($key) {
        $result = $this->db->select("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
        return !empty($result) ? $result[0]['setting_value'] : null;
    }
    
    /**
     * บันทึกการตั้งค่า
     */
    public function setSetting($key, $value) {
        $existing = $this->db->select("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
        if (!empty($existing)) {
            return $this->db->update('site_settings', ['setting_value' => $value], "setting_key = '" . $this->db->escape($key) . "'");
        } else {
            return $this->db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
    }
    
    /**
     * บันทึก prompt ที่ผู้ใช้สร้าง
     */
    public function saveUserPrompt($data) {
        $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return $this->db->insert('user_prompts', $data);
    }
    
    /**
     * ดึงสถิติการใช้งาน
     */
    public function getStats() {
        $stats = [];
        
        try {
            $total_examples = $this->db->select("SELECT COUNT(*) as count FROM prompt_examples");
            $stats['total_examples'] = !empty($total_examples) ? $total_examples[0]['count'] : 0;
            
            $total_gallery = $this->db->select("SELECT COUNT(*) as count FROM gallery_items");
            $stats['total_gallery'] = !empty($total_gallery) ? $total_gallery[0]['count'] : 0;
            
            $total_user_prompts = $this->db->select("SELECT COUNT(*) as count FROM user_prompts");
            $stats['total_user_prompts'] = !empty($total_user_prompts) ? $total_user_prompts[0]['count'] : 0;
            
            $today_prompts = $this->db->select("SELECT COUNT(*) as count FROM user_prompts WHERE DATE(created_at) = CURDATE()");
            $stats['today_prompts'] = !empty($today_prompts) ? $today_prompts[0]['count'] : 0;
        } catch (Exception $e) {
            error_log('Error in getStats: ' . $e->getMessage());
            $stats = [
                'total_examples' => 0,
                'total_gallery' => 0,
                'total_user_prompts' => 0,
                'today_prompts' => 0
            ];
        }
        
        return $stats;
    }
}

/**
 * ฟังก์ชันช่วยเหลือ
 */

/**
 * แสดงข้อความแจ้งเตือน JSON
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * ป้องกัน XSS
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * ตรวจสอบ Admin
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * ล็อกอิน Admin
 */
function adminLogin($password) {
    if ($password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        return true;
    }
    return false;
}

/**
 * ล็อกเอาต์ Admin
 */
function adminLogout() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_login_time']);
    session_destroy();
}

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
 */
function isUserLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * ดึงข้อมูลผู้ใช้ปัจจุบัน
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    $db = Database::getInstance();
    $user_id = $_SESSION['user_id'];
    $user = $db->select("SELECT * FROM users WHERE id = ?", [$user_id]);
    return !empty($user) ? $user[0] : null;
}

/**
 * ตรวจสอบ URL รูปภาพ
 */
function isValidImageUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    $headers = @get_headers($url, 1);
    if (!$headers) {
        return false;
    }
    
    $contentType = $headers['Content-Type'] ?? '';
    if (is_array($contentType)) {
        $contentType = $contentType[0];
    }
    
    return strpos($contentType, 'image/') === 0;
}

/**
 * สร้าง log
 */
function writeLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/logs/app.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// ดึงข้อมูลเพื่อแสดงในหน้าหลัก
function getPageData() {
    $promptManager = new PromptManager();
    
    return [
        'examples' => $promptManager->getAllExamples(),
        'gallery' => $promptManager->getAllGalleryItems(),
        'settings' => [
            'site_title' => $promptManager->getSetting('site_title'),
            'site_description' => $promptManager->getSetting('site_description'),
            'online_count' => $promptManager->getSetting('online_count'),
            'placeholder_title' => $promptManager->getSetting('placeholder_title'),
            'placeholder_description' => $promptManager->getSetting('placeholder_description'),
            'gallery_title' => $promptManager->getSetting('gallery_title'),
            'gallery_description' => $promptManager->getSetting('gallery_description')
        ]
    ];
}

// สร้างตารางเมื่อโหลดไฟล์ config.php ครั้งแรก
try {
    $promptManager = new PromptManager();
    $promptManager->createTables();
} catch (Exception $e) {
    writeLog('Error initializing database: ' . $e->getMessage(), 'ERROR');
    error_log('Config initialization error: ' . $e->getMessage());
}

// ปิดการแสดงข้อผิดพลาด PHP ในโหมด production
if (!defined('DEBUG_MODE')) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>