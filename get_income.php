<?php
session_start();
$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$res = $conn->query("SELECT SUM(amount) as total FROM user_earnings WHERE user_id = $user_id");
$row = $res->fetch_assoc();
$total = $row['total'] ?? 0;

echo json_encode([
    'success' => true,
    'totalIncome' => number_format($total, 2)
]);
