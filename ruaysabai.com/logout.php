<?php
// เริ่ม session
session_start();

// ลบข้อมูลทั้งหมดใน session
$_SESSION = [];

// ลบ cookie ของ session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// ตั้งค่าข้อความแจ้งเตือนสำหรับหน้าล็อกอิน
session_start();
$_SESSION['login_message'] = 'ออกจากระบบสำเร็จ';

// Redirect ไปยังหน้าล็อกอิน
header('Location: login.php');
exit;
?>