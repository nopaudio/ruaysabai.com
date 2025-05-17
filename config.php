<?php
$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0004441558';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>