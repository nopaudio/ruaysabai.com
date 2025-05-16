<?php
// เริ่มเซสชัน
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?redirect=admin/settings.php');
    exit();
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once('../config.php');

// แสดงข้อผิดพลาดเพื่อดีบัก
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ดึงการตั้งค่า
$settings = [
    'site_title' => 'ระบบหวยออนไลน์',
    'site_description' => 'เว็บแทงหวยออนไลน์ บาทละ 900',
    'contact_phone' => '0800000000',
    'contact_line' => '@lotteryonline',
    'min_deposit' => '100',
    'max_deposit' => '10000',
    'min_withdrawal' => '100',
    'max_withdrawal' => '10000',
    'bank_account_name' => 'บริษัท หวยออนไลน์ จำกัด',
    'bank_account_number' => '1234567890',
    'bank_name' => 'ธนาคารกสิกรไทย',
    'promptpay_number' => '0800000000',
    'maintenance_mode' => '0',
    'registration_enabled' => '1'
];

// อัตราการจ่าย
$pay_rates = [
    '2up' => '90',
    '2down' => '90',
    '3up' => '700',
    '3tod' => '120',
    'run_top' => '3',
    'run_down' => '4'
];

// บันทึกการตั้งค่า - ไม่มีการบันทึกจริงในเวอร์ชั่นทดสอบนี้
$success_message = '';
$error_message = '';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการตั้งค่า - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>จัดการการตั้งค่าระบบ</h1>
        <p>หน้าสำหรับตั้งค่าระบบหวยออนไลน์</p>
        <div class="alert alert-info">
            หน้านี้เป็นเวอร์ชั่นทดสอบสำหรับค้นหาปัญหา
        </div>
        
        <div class="card">
            <div class="card-header">
                ตั้งค่าทั่วไป
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label for="site_title" class="form-label">ชื่อเว็บไซต์</label>
                        <input type="text" class="form-control" id="site_title" value="<?php echo htmlspecialchars($settings['site_title']); ?>" disabled>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
