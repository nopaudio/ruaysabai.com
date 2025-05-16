<?php
// ไฟล์เชื่อมต่อกับฐานข้อมูล
$host = 'localhost';
$username = 'xxvdoxxc_ruaysabai';
$password = '0804441958';
$database = 'xxvdoxxc_ruaysabai';

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $username, $password, $database);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่าภาษา
$conn->set_charset("utf8mb4");
?>