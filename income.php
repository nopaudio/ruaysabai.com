<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// ตรวจสอบระดับสมาชิก
$levelMap = [
  'free' => ['minutes' => 5, 'min' => 5, 'max' => 15],
  'premium' => ['minutes' => 3, 'min' => 10, 'max' => 30],
  'vip' => ['minutes' => 1, 'min' => 15, 'max' => 50]
];
$level = 'free';
$res = $conn->query("SELECT level FROM users WHERE id = $user_id");
if ($res->num_rows > 0) {
  $level = $res->fetch_assoc()['level'] ?? 'free';
}
$settings = $levelMap[$level];

// ตรวจสอบเวลา cooldown
$last = $conn->query("SELECT earned_at FROM user_earnings WHERE user_id = $user_id ORDER BY earned_at DESC LIMIT 1");
$allow = true;
if ($last->num_rows > 0) {
    $last_time = new DateTime($last->fetch_assoc()['earned_at']);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $last_time->getTimestamp();
    if ($diff < $settings['minutes'] * 60) {
        $allow = false;
    }
}

if (!$allow) {
    echo json_encode(['success' => false, 'message' => 'ยังไม่ครบเวลารับรายได้']);
    exit;
}

// รับรายได้
$amount = rand($settings['min'], $settings['max']);
$conn->query("INSERT INTO user_earnings (user_id, amount) VALUES ($user_id, $amount)");

// ✅ เพิ่มโบนัสแนะนำเพื่อน
$ref = $conn->query("SELECT ref_by FROM users WHERE id = $user_id");
if ($ref->num_rows > 0) {
    $ref_by = $ref->fetch_assoc()['ref_by'];
    if (!empty($ref_by)) {
        $ref_user = $conn->query("SELECT id FROM users WHERE username = '$ref_by'");
        if ($ref_user->num_rows > 0) {
            $ref_id = $ref_user->fetch_assoc()['id'];
            $bonus = floor($amount * 0.10);
            $conn->query("INSERT INTO ref_commissions (referrer_id, referred_id, amount, type) VALUES ($ref_id, $user_id, $bonus, 'income')");
        }
    }
}

echo json_encode(['success' => true, 'amount' => $amount]);
?>
