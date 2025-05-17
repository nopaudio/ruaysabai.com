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

    echo "DEBUG: Username: $username<br>";
    echo "DEBUG: Password: $password<br>";

    $check = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $user = $check->fetch_assoc();
        echo "DEBUG: Found user. DB Hash: {$user['password']}<br>";

        if (password_verify($password, $user["password"])) {
            echo "✅ Password match<br>";
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "❌ password_verify() failed<br>";
        }
    } else {
        echo "❌ ไม่พบชื่อผู้ใช้ในระบบ<br>";
    }

    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>เข้าสู่ระบบ | AISmartCash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
  <div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
    <h1 class="text-2xl font-bold mb-6 text-center">🔐 เข้าสู่ระบบ</h1>

    <?php if (!empty($error)): ?>
      <div class="bg-red-500 text-white px-4 py-2 rounded mb-4 text-center"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <input type="text" name="username" placeholder="ชื่อผู้ใช้" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <input type="password" name="password" placeholder="รหัสผ่าน" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-400" required>
      <button type="submit" class="w-full bg-lime-500 hover:bg-lime-600 text-white py-2 rounded font-bold">➡️ เข้าสู่ระบบ</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-300">
      ยังไม่มีบัญชี? <a href="register.php" class="text-lime-400 hover:underline">สมัครสมาชิก</a>
    </p>
  </div>
</body>
</html>
