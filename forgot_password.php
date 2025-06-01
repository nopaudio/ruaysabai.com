<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
} else {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $email = trim($data['email'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'กรุณากรอกอีเมลให้ถูกต้อง');
    }
    $db = Database::getInstance();
    $user = $db->select("SELECT id FROM users WHERE email = ?", [$email]);
    $user = $user[0] ?? null;
    if (!$user) {
        jsonResponse(false, 'ไม่พบผู้ใช้นี้ในระบบ');
    }
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $db->update('users', [
        'reset_token' => $token,
        'reset_token_expires' => $expires
    ], "id = {$user['id']}");
    // MOCKUP ส่งลิงก์
    $link = "https://".$_SERVER['HTTP_HOST']."/reset_password.php?token=$token";
    writeLog("Password reset link for $email: $link");
    jsonResponse(true, "ส่งลิงก์เปลี่ยนรหัสผ่านไปยังอีเมล (mockup: $link)");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ลืมรหัสผ่าน</title>
</head>
<body>
    <h2>ลืมรหัสผ่าน</h2>
    <form method="post" action="forgot_password.php">
        <label>Email: <input type="email" name="email" required></label><br>
        <button type="submit">ขอเปลี่ยนรหัสผ่าน</button>
    </form>
    <p><a href="login.php">เข้าสู่ระบบ</a></p>
</body>
</html>