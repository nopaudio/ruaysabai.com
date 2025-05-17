<?php
session_start();
$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];
    $email = trim($_POST["email"]);

    if (empty($username) || empty($password) || empty($confirm) || empty($email)) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif ($password !== $confirm) {
        $error = "รหัสผ่านไม่ตรงกัน";
    } else {
        $check = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if ($check->num_rows > 0) {
            $error = "ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $ref = isset($_GET['ref']) ? $_GET['ref'] : '';
            $sql = "INSERT INTO users (username, password, email, ref_by, level) VALUES ('$username', '$hashed', '$email', '$ref', 'free')";
            if ($conn->query($sql) === TRUE) {
                header("Location: login.php");
                exit;
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>สมัครสมาชิก | AISmartCash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
  <div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
    <h1 class="text-2xl font-bold mb-6 text-center">📝 สมัครสมาชิกใหม่</h1>

    <?php if (!empty($error)): ?>
      <div class="bg-red-500 text-white px-4 py-2 rounded mb-4 text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="text" name="username" placeholder="ชื่อผู้ใช้" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <input type="email" name="email" placeholder="อีเมล" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <input type="password" name="password" placeholder="รหัสผ่าน" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <input type="password" name="confirm" placeholder="ยืนยันรหัสผ่าน" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <button type="submit" class="w-full bg-lime-500 hover:bg-lime-600 text-white py-2 rounded font-bold">✅ สมัครสมาชิก</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-300">
      มีบัญชีอยู่แล้ว? <a href="login.php" class="text-lime-400 hover:underline">เข้าสู่ระบบ</a>
    </p>
  </div>
</body>
</html>
