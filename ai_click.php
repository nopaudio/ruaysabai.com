<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "กรุณาเข้าสู่ระบบก่อน"]);
    exit;
}

// ป้องกันกดบ่อยเกินไป (1 วิ)
if (isset($_SESSION["last_click_time"])) {
    $diff = time() - $_SESSION["last_click_time"];
    if ($diff < 1) {
        echo json_encode(["success" => false, "message" => "คุณกดเร็วเกินไป กรุณารอสักครู่"]);
        exit;
    }
}
$_SESSION["last_click_time"] = time();

// สุ่มรายได้ระหว่าง 0.10 - 0.50
$amount = round(mt_rand(10, 50) / 100, 2);

// โอกาสสุ่มเด้ง Shopee Affiliate (1 ใน 8)
$show_affiliate = mt_rand(1, 8) == 1;
$shopee_url = "https://s.shopee.co.th/3fqoCCC1ZZ"; // ใส่ลิงก์จริง

// เชื่อมต่อฐานข้อมูล
$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'เชื่อมต่อฐานข้อมูลล้มเหลว']);
    exit;
}

// อัปเดตยอดและเพิ่มประวัติ
$user_id = $_SESSION["user_id"];
$conn->query("UPDATE users SET balance = balance + $amount WHERE id = $user_id");
$conn->query("INSERT INTO user_earnings (user_id, amount) VALUES ($user_id, $amount)");

// ส่งค่ากลับ
echo json_encode([
    "success" => true,
    "amount" => number_format($amount, 2),
    "show_affiliate" => $show_affiliate,
    "shopee_url" => $show_affiliate ? $shopee_url : null
]);
?>
