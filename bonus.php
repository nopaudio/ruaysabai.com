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

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$check = $conn->query("SELECT id FROM user_bonus WHERE user_id = $user_id AND bonus_at = '$today'");
$alreadyClaimed = $check->num_rows > 0;
$bonusAmount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyClaimed) {
  $bonusAmount = rand(5, 50);
  $conn->query("INSERT INTO user_bonus (user_id, amount, bonus_at) VALUES ($user_id, $bonusAmount, '$today')");
}

$totalBonus = $conn->query("SELECT SUM(amount) as total FROM user_bonus WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>กล่องโบนัส – AISmartCash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-yellow-300 to-red-400 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded-xl shadow-xl w-full max-w-md text-center">
    <h1 class="text-3xl font-bold text-red-600 mb-4">🎁 กล่องโบนัสรายวัน</h1>
    <p class="mb-2 text-gray-700">คุณสามารถเปิดกล่องสุ่มได้วันละ 1 ครั้ง</p>
    <div class="text-xl text-green-600 font-semibold mb-4">โบนัสสะสมทั้งหมด: ฿<?php echo $totalBonus; ?></div>

    <?php if ($alreadyClaimed): ?>
      <div class="text-yellow-600 font-medium mb-4">คุณเปิดกล่องแล้ววันนี้ 🎉 กลับมาใหม่พรุ่งนี้!</div>
    <?php elseif ($bonusAmount): ?>
      <div class="text-2xl text-blue-600 font-bold mb-4">คุณได้รับโบนัส +฿<?php echo $bonusAmount; ?> 🎉</div>
    <?php endif; ?>

    <?php if (!$alreadyClaimed && !$bonusAmount): ?>
      <form method="post">
        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-2 px-6 rounded-full font-semibold text-lg shadow-md">เปิดกล่องสุ่มเลย!</button>
      </form>
    <?php endif; ?>

    <div class="mt-6">
      <a href="dashboard.php" class="text-sm text-indigo-600 hover:underline">⬅ กลับสู่แดชบอร์ด</a>
    </div>
  </div>
</body>
</html>
