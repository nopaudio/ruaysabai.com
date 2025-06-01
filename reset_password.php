<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (!$token || !$new_password) {
        jsonResponse(false, 'ข้อมูลไม่ครบถ้วน');
    }
    $db = Database::getInstance();
    $user = $db->select("SELECT id, reset_token_expires FROM users WHERE reset_token = ?", [$token]);
    $user = $user[0] ?? null;
    if (!$user || strtotime($user['reset_token_expires']) < time()) {
        jsonResponse(false, 'โทเค็นไม่ถูกต้องหรือหมดอายุ');
    }
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $db->update('users', [
        'password_hash' => $password_hash,
        'reset_token' => null,
        'reset_token_expires' => null
    ], "id = {$user['id']}");
    jsonResponse(true, 'เปลี่ยนรหัสผ่านสำเร็จ');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>รีเซ็ตรหัสผ่าน</title>
</head>
<body>
    <h2>รีเซ็ตรหัสผ่าน</h2>
    <form method="post" action="reset_password.php">
        <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
        <label>รหัสผ่านใหม่: <input type="password" name="new_password" required></label><br>
        <button type="submit">เปลี่ยนรหัสผ่าน</button>
    </form>
    <p><a href="login.php">เข้าสู่ระบบ</a></p>
</body>
</html>