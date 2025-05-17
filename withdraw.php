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
$message = "";

// รวมรายได้จาก user_earnings
$sum = $conn->query("SELECT SUM(amount) as total FROM user_earnings WHERE user_id = $user_id");
$totalIncome = $sum->fetch_assoc()['total'] ?? 0;

// รวมยอดถอนก่อนหน้า
$withdrawn = $conn->query("SELECT SUM(amount) as total FROM user_withdraw WHERE user_id = $user_id");
$totalWithdrawn = $withdrawn->fetch_assoc()['total'] ?? 0;

// คำนวณยอดที่ถอนได้
$available = $totalIncome - $totalWithdrawn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['wallet'])) {
  $amount = intval($_POST['amount']);
  $wallet = $conn->real_escape_string(trim($_POST['wallet']));
  if ($amount > 0 && $amount <= $available && strlen($wallet) >= 10) {
    $conn->query("INSERT INTO user_withdraw (user_id, amount, wallet, status) VALUES ($user_id, $amount, '$wallet', 'pending')");
    $message = "✅ ส่งคำขอถอนเงินแล้ว กรุณารอการตรวจสอบ";
    $available -= $amount;
  } else {
    $message = "❌ ยอดเงินไม่ถูกต้อง หรือข้อมูลบัญชีไม่ครบถ้วน";
  }
}
?>
<!DOCTYPE html>
<html lang='th'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>ถอนเงิน – AISmartCash</title>
  <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center'>
  <div class='bg-white p-8 rounded-xl shadow-xl w-full max-w-md'>
    <h1 class='text-2xl font-bold mb-4 text-center text-rose-700'>💸 ถอนเงินออก</h1>
    <p class='text-center text-gray-600 mb-2'>ยอดที่สามารถถอนได้:</p>
    <div class='text-4xl text-green-600 font-bold text-center mb-4'>฿<?php echo $available; ?></div>

    <?php if ($message): ?>
      <div class='mb-4 text-center font-medium text-blue-600'><?php echo $message; ?></div>
    <?php endif; ?>

    <form method='post' class='space-y-4'>
      <label class='block text-sm font-medium text-gray-700'>จำนวนเงินที่ต้องการถอน</label>
      <input type='number' name='amount' max='<?php echo $available; ?>' class='w-full border px-4 py-2 rounded-lg' required>

      <label class='block text-sm font-medium text-gray-700'>เลขพร้อมเพย์ (หรือบัญชี)</label>
      <input type='text' name='wallet' class='w-full border px-4 py-2 rounded-lg' required>

      <button type='submit' class='w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-lg'>ส่งคำขอถอนเงิน</button>
    </form>

    <div class='text-center mt-6'>
      <a href='dashboard.php' class='text-sm text-indigo-600 hover:underline'>⬅ กลับสู่แดชบอร์ด</a>
    </div>
  </div>
</body>
</html>
