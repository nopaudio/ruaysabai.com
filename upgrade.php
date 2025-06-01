<?php
require_once 'config.php';
requireLogin();
$user = getCurrentUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = connectDB();
    $stmt = $db->prepare("UPDATE users SET user_type='vip' WHERE id=?");
    $stmt->execute([$user['id']]);
    $_SESSION['user'] = getUserById($user['id']);
    echo "อัปเกรด VIP สำเร็จ <a href='index.php'>กลับหน้าหลัก</a>";
    exit;
}
?>
<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><title>อัปเกรด VIP</title></head>
<body>
<h2>อัปเกรดสมาชิก VIP</h2>
<form method="post"><button type="submit">อัปเกรดเป็น VIP (ทดสอบ)</button></form>
<a href="index.php">กลับหน้าหลัก</a>
</body></html>