<?php
// เริ่มต้น session
session_start();

// ล้าง session variables
$_SESSION = array();

// ถ้ามีการใช้ session cookie ให้ลบ cookie ด้วย
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// เปลี่ยนเส้นทางไปยังหน้าล็อกอิน
header("Location: index.php");
exit();
?>