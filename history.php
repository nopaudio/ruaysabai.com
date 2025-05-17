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
    die('Connection failed: ' . $conn->connect_error);
}
date_default_timezone_set('Asia/Bangkok');


$user_id = $_SESSION['user_id'];
$wallets = $conn->query("SELECT amount, created_at FROM user_wallet WHERE user_id = $user_id ORDER BY created_at DESC");
$earnings = $conn->query("SELECT amount, earned_at FROM user_earnings WHERE user_id = $user_id ORDER BY earned_at DESC");
$bonuses = $conn->query("SELECT amount, bonus_at FROM user_bonus WHERE user_id = $user_id ORDER BY bonus_at DESC");
$withdraws = $conn->query("SELECT amount, wallet, status, created_at FROM user_withdraw WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ประวัติย้อนหลัง – AISmartCash</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen p-4">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-indigo-700 mb-6 text-center">📜 ประวัติย้อนหลัง</h1>

    <div class="space-y-8">

      <div>
        <h2 class="text-lg font-semibold mb-2">💵 เติมเงิน</h2>
        <div class="bg-white rounded shadow p-4 overflow-x-auto">
          <ul class="text-sm divide-y">
            <?php while($row = $wallets->fetch_assoc()): ?>
              <li class="py-2">+฿<?php echo $row['amount']; ?> <span class="text-gray-500">(<?php echo $row['created_at']; ?>)</span></li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>

      <div>
        <h2 class="text-lg font-semibold mb-2">💸 รับรายได้</h2>
        <div class="bg-white rounded shadow p-4 overflow-x-auto">
          <ul class="text-sm divide-y">
            <?php while($row = $earnings->fetch_assoc()): ?>
              <li class="py-2">+฿<?php echo $row['amount']; ?> <span class="text-gray-500">(<?php echo $row['earned_at']; ?>)</span></li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>

      <div>
        <h2 class="text-lg font-semibold mb-2">🎁 โบนัส</h2>
        <div class="bg-white rounded shadow p-4 overflow-x-auto">
          <ul class="text-sm divide-y">
            <?php while($row = $bonuses->fetch_assoc()): ?>
              <li class="py-2">+฿<?php echo $row['amount']; ?> <span class="text-gray-500">(<?php echo $row['bonus_at']; ?>)</span></li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>

      <div>
        <h2 class="text-lg font-semibold mb-2">🏦 ถอนเงิน</h2>
        <div class="bg-white rounded shadow p-4 overflow-x-auto">
          <ul class="text-sm divide-y">
            <?php while($row = $withdraws->fetch_assoc()): ?>
              <li class="py-2">-฿<?php echo $row['amount']; ?> → <?php echo $row['wallet']; ?> <span class="text-gray-500">(<?php echo $row['created_at']; ?>)</span>
                <span class="ml-2 font-medium <?php echo $row['status'] === 'approved' ? 'text-green-600' : ($row['status'] === 'rejected' ? 'text-red-600' : 'text-yellow-600'); ?>">[<?php echo strtoupper($row['status']); ?>]</span>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>

    </div>

    <div class="text-center mt-8">
      <a href="dashboard.php" class="text-indigo-600 hover:underline">⬅ กลับแดชบอร์ด</a>
    </div>
  </div>
</body>
</html>
