<?php
require_once 'config.php';

// ทำลาย session
session_unset();
session_destroy();

// เริ่ม session ใหม่เพื่อแสดงข้อความ
session_start();
$_SESSION['logout_success'] = true;

// ไปหน้า login
header('Location: login.php');
exit;
?>