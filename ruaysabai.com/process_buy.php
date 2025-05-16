<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

if (!isset($_SESSION)) {
    session_start();
}

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "กรุณาเข้าสู่ระบบก่อนซื้อหวย";
    $_SESSION['message_type'] = "warning";
    header("Location: login.php");
    exit;
}

// ตรวจสอบว่ามีการส่งฟอร์มมา
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['message'] = "การเข้าถึงไม่ถูกต้อง";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

// รับข้อมูลจากฟอร์ม
$user_id = $_SESSION['user_id'];
$lottery_id = $_POST['lottery_id'];
$lottery_type = $_POST['lottery_type'];
$number = $_POST['number'];
$amount = floatval($_POST['amount']);

// ตรวจสอบข้อมูล
$errors = [];

// ตรวจสอบงวด
if (empty($lottery_id)) {
    $errors[] = "ไม่พบข้อมูลงวด";
}

// ตรวจสอบประเภทหวย
$valid_types = ['first_prize', 'last_two', 'first_three', 'last_three'];
if (!in_array($lottery_type, $valid_types)) {
    $errors[] = "ประเภทหวยไม่ถูกต้อง";
}

// ตรวจสอบเลขที่ซื้อ
switch ($lottery_type) {
    case 'first_prize':
        if (!preg_match('/^[0-9]{6}$/', $number)) {
            $errors[] = "เลขรางวัลที่ 1 ต้องเป็นตัวเลข 6 หลัก";
        }
        break;
    case 'last_two':
        if (!preg_match('/^[0-9]{2}$/', $number)) {
            $errors[] = "เลข 2 ตัวท้ายต้องเป็นตัวเลข 2 หลัก";
        }
        break;
    case 'first_three':
    case 'last_three':
        if (!preg_match('/^[0-9]{3}$/', $number)) {
            $errors[] = "เลข 3 ตัวต้องเป็นตัวเลข 3 หลัก";
        }
        break;
}

// ตรวจสอบจำนวนเงิน
$min_bet = intval(getSetting('general', 'min_bet', 1));
$max_bet = intval(getSetting('general', 'max_bet', 1000));

if ($amount < $min_bet || $amount > $max_bet) {
    $errors[] = "จำนวนเงินต้องอยู่ระหว่าง {$min_bet} - {$max_bet} บาท";
}

// ตรวจสอบว่างวดนี้ยังเปิดรับซื้ออยู่หรือไม่
if (isLotteryClosed($lottery_id)) {
    $errors[] = "งวดนี้ปิดรับซื้อแล้ว";
}

// ตรวจสอบว่าเลขนี้ซื้อไปแล้วหรือยัง
if (isNumberAlreadyPurchased($user_id, $lottery_id, $number, $lottery_type)) {
    $errors[] = "คุณได้ซื้อเลข $number ในประเภท ".getLotteryTypeText($lottery_type)." ไปแล้ว";
}

// ตรวจสอบยอดเงิน
$balance = getUserBalance($user_id);
if ($balance < $amount) {
    $errors[] = "ยอดเงินไม่เพียงพอ กรุณาเติมเงินก่อน";
}

// ถ้ามีข้อผิดพลาด
if (!empty($errors)) {
    $_SESSION['message'] = "เกิดข้อผิดพลาด: " . implode(", ", $errors);
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

// ทำธุรกรรมซื้อหวย
$conn->begin_transaction();

try {
    // บันทึกการซื้อหวย
    $stmt = $conn->prepare("INSERT INTO lottery_tickets (user_id, lottery_id, numbers, amount, type, status, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'active', NOW())");
    $stmt->bind_param("iisds", $user_id, $lottery_id, $number, $amount, $lottery_type);
    $stmt->execute();
    $ticket_id = $stmt->insert_id;
    
    // อัปเดตยอดเงิน
    $new_balance = $balance - $amount;
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $new_balance, $user_id);
    $stmt->execute();
    
    // บันทึกธุรกรรม
    addTransaction($user_id, 'purchase', $amount, "ซื้อหวย ".getLotteryTypeText($lottery_type)." เลข $number", $ticket_id);
    
    // ส่งการแจ้งเตือน
    notifyPurchase($user_id, $number, $amount);
    
    $conn->commit();
    
    $_SESSION['message'] = "ซื้อหวยเรียบร้อยแล้ว! เลข $number ประเภท ".getLotteryTypeText($lottery_type)." จำนวน ".number_format($amount, 2)." บาท";
    $_SESSION['message_type'] = "success";
    
    header("Location: history.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    
    $_SESSION['message'] = "เกิดข้อผิดพลาดในการซื้อหวย: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    
    header("Location: index.php");
    exit;
}
?>