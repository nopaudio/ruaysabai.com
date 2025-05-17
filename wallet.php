<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = floatval($_POST["amount"]);
    if ($amount > 0) {
        // เติมเงินเข้ายอด
        $conn->query("UPDATE users SET balance = balance + $amount WHERE id = $user_id");
        $conn->query("INSERT INTO topups (user_id, amount) VALUES ($user_id, $amount)");
        
        // ✅ ให้ค่าคอมผู้แนะนำ (ถ้ามี)
        $ref = $conn->query("SELECT ref_by FROM users WHERE id = $user_id");
        if ($ref->num_rows > 0) {
            $ref_by = $ref->fetch_assoc()['ref_by'];
            if (!empty($ref_by)) {
                $ref_user = $conn->query("SELECT id FROM users WHERE username = '$ref_by'");
                if ($ref_user->num_rows > 0) {
                    $ref_id = $ref_user->fetch_assoc()['id'];
                    $bonus = floor($amount * 0.10);
                    $conn->query("INSERT INTO ref_commissions (referrer_id, referred_id, amount, type) VALUES ($ref_id, $user_id, $bonus, 'topup')");
                }
            }
        }

        $message = "✅ เติมเงินสำเร็จแล้ว!";
    } else {
        $message = "❌ กรุณาระบุจำนวนเงินที่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เติมเงิน | AISmartCash</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.js" defer></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body class="bg-gray-900 text-white p-6 font-sans">

  <div class="max-w-xl mx-auto bg-gray-800 p-6 rounded-lg shadow-lg" x-data="{ open: false }">
    <h1 class="text-3xl font-extrabold text-center mb-6">💵 เติมเงินเข้าสู่ระบบ</h1>
    
    <?php if (!empty($message)): ?>
      <div class="mb-4 text-center text-green-400 font-semibold"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div class="flex flex-col">
        <label class="text-sm font-medium">จำนวนเงิน (บาท):</label>
        <input type="number" name="amount" step="1" min="1" class="w-full px-4 py-3 rounded text-black mt-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
      </div>

      <div class="flex items-center justify-between">
        <button class="bg-green-500 hover:bg-green-600 px-6 py-3 rounded text-white font-semibold w-full">เติมเงิน</button>
        <span class="text-green-500 text-xl ml-4"><i class="fas fa-wallet"></i></span>
      </div>
    </form>

    <div class="mt-4 text-center">
      <a href="dashboard.php" class="text-blue-300 hover:underline">
        <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก
      </a>
    </div>
  </div>
</body>
</html>
