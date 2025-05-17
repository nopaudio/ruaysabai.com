<?php
session_start();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>ผู้ดูแลระบบ</title></head>
<body>
<h2>ผู้ดูแลระบบ</h2>
<p>หน้านี้สำหรับดูแลระบบ</p>
<a href="dashboard.php">กลับ</a>
</body>
</html>