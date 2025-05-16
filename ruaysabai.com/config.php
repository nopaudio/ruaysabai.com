<?php
// กำหนดค่าสำหรับการเชื่อมต่อฐานข้อมูล MySQL
$db_config = [
    'host' => 'localhost',
    'username' => 'xxvdoxxc_ruaysabai',
    'password' => '0804441958',
    'database' => 'xxvdoxxc_ruaysabai',
    'charset' => 'utf8mb4'
];

// ฟังก์ชันสำหรับเชื่อมต่อฐานข้อมูล
function connectDB() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $db_config['username'], $db_config['password'], $options);
    } catch (PDOException $e) {
        // บันทึกข้อผิดพลาดลงในไฟล์
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

// ค่าคงที่สำหรับระบบ
define('SITE_NAME', 'ระบบหวยออนไลน์');
define('SITE_DESCRIPTION', 'เว็บไซต์หวยออนไลน์ที่มั่นคง ปลอดภัย จ่ายจริง');
define('ADMIN_EMAIL', 'admin@lotterythai.com');
define('SUPPORT_EMAIL', 'support@lotterythai.com');
define('CONTACT_PHONE', '02-123-4567');
define('LINE_ID', '@lotterythai');

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');

// รายการอัตราการจ่ายเงิน (บาทละเท่าไร)
$payment_rates = [
    'firstPrize' => 900,   // รางวัลที่ 1
    'frontThree' => 500,   // เลขหน้า 3 ตัว
    'backThree' => 500,    // เลขท้าย 3 ตัว
    'backTwo' => 90        // เลขท้าย 2 ตัว
];

// สถานะการเติมเงิน
$deposit_statuses = [
    'pending' => 'รอตรวจสอบ',
    'success' => 'สำเร็จ',
    'failed' => 'ไม่สำเร็จ'
];

// สถานะการซื้อหวย
$lottery_statuses = [
    'pending' => 'รอผล',
    'win' => 'ถูกรางวัล',
    'lose' => 'ไม่ถูกรางวัล'
];

// ช่องทางการเติมเงิน
$payment_methods = [
    'bank' => 'โอนผ่านธนาคาร',
    'promptpay' => 'พร้อมเพย์',
    'truemoney' => 'ทรูมันนี่วอลเล็ท'
];

// ข้อมูลบัญชีธนาคาร
$bank_accounts = [
    [
        'bank' => 'kbank',
        'name' => 'ธนาคารกสิกรไทย',
        'number' => '123-4-56789-0',
        'account_name' => 'บริษัท หวยออนไลน์ จำกัด'
    ],
    [
        'bank' => 'scb',
        'name' => 'ธนาคารไทยพาณิชย์',
        'number' => '987-6-54321-0',
        'account_name' => 'บริษัท หวยออนไลน์ จำกัด'
    ]
];

// ข้อมูลพร้อมเพย์
$promptpay = [
    'number' => '1234567890123',
    'name' => 'บริษัท หวยออนไลน์ จำกัด'
];

// ข้อมูลทรูมันนี่วอลเล็ท
$truemoney = [
    'number' => '089-123-4567',
    'name' => 'บริษัท หวยออนไลน์ จำกัด'
];

// ฟังก์ชันสำหรับเข้ารหัสรหัสผ่าน
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// ฟังก์ชันสำหรับตรวจสอบรหัสผ่าน
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// ฟังก์ชันสำหรับสร้างเลขอ้างอิง
function generateReference($prefix = 'TX') {
    return $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// ฟังก์ชันสำหรับตรวจสอบการล็อกอิน
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ฟังก์ชันสำหรับตรวจสอบว่าเป็นแอดมินหรือไม่
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// ฟังก์ชันสำหรับเปลี่ยนสถานะเป็นข้อความภาษาไทย
function getStatusText($status, $type = 'deposit') {
    global $deposit_statuses, $lottery_statuses;
    
    if ($type === 'deposit') {
        return $deposit_statuses[$status] ?? $status;
    } else if ($type === 'lottery') {
        return $lottery_statuses[$status] ?? $status;
    }
    
    return $status;
}

// ฟังก์ชันสำหรับเปลี่ยนสถานะเป็น CSS class
function getStatusClass($status) {
    switch ($status) {
        case 'success':
        case 'win':
            return 'status-success';
        case 'pending':
            return 'status-pending';
        case 'failed':
        case 'lose':
            return 'status-failed';
        default:
            return '';
    }
}

// ฟังก์ชันสำหรับแสดงผลข้อความแจ้งเตือน
function showAlert($message, $type = 'success') {
    if (!empty($message)) {
        echo '<div class="alert alert-' . $type . '">';
        echo '<i class="fas fa-' . ($type === 'success' ? 'check-circle' : ($type === 'danger' ? 'exclamation-circle' : 'info-circle')) . '"></i> ';
        echo $message;
        echo '</div>';
    }
}

// ฟังก์ชันสำหรับแสดงผลข้อความแจ้งเตือนจาก session
function showSessionAlert($key = 'message', $type = 'success') {
    if (isset($_SESSION[$key])) {
        showAlert($_SESSION[$key], $type);
        unset($_SESSION[$key]);
    }
}
?>