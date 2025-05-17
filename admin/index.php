<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
  header('Location: ../login.php');
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

// จัดการคำขออนุมัติ / ปฏิเสธ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['withdraw_id'], $_POST['action'])) {
    $id = intval($_POST['withdraw_id']);
    $action = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';
    $conn->query("UPDATE user_withdraw SET status = '$action' WHERE id = $id");
  }

  if (isset($_POST['announcement'])) {
    file_put_contents('../notice.txt', trim($_POST['announcement']));
  }
}

$withdraws = $conn->query("SELECT * FROM user_withdraw ORDER BY created_at DESC");
$users = $conn->query("SELECT u.id, u.username, 
  (SELECT SUM(amount) FROM user_earnings WHERE user_id = u.id) as income,
  (SELECT SUM(amount) FROM user_wallet WHERE user_id = u.id) as topup,
  (SELECT SUM(amount) FROM user_bonus WHERE user_id = u.id) as bonus
FROM users u");

$currentNotice = file_exists('../notice.txt') ? file_get_contents('../notice.txt') : '';
?>
<!DOCTYPE html>
<html lang='th'>
<head>
  <meta charset='UTF-8'>
  <title>Admin Panel – AISmartCash</title>
  <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 text-gray-800 min-h-screen'>
  <div class='max-w-6xl mx-auto p-4'>
    <h1 class='text-3xl font-bold text-indigo-700 mb-6'>📊 Admin Panel – AISmartCash</h1>

    <div class='grid md:grid-cols-2 gap-8'>

      <div>
        <h2 class='text-xl font-semibold mb-2'>📥 คำขอถอนเงิน</h2>
        <div class='bg-white p-4 rounded shadow h-96 overflow-y-scroll text-sm'>
          <table class='w-full'>
            <thead><tr><th>ID</th><th>ผู้ใช้</th><th>ยอด</th><th>บัญชี</th><th>เวลา</th><th>สถานะ</th><th>จัดการ</th></tr></thead>
            <tbody>
              <?php while($w = $withdraws->fetch_assoc()): ?>
              <tr class='border-b'>
                <td><?php echo $w['id']; ?></td>
                <td><?php echo $w['user_id']; ?></td>
                <td>฿<?php echo $w['amount']; ?></td>
                <td><?php echo $w['wallet']; ?></td>
                <td><?php echo $w['created_at']; ?></td>
                <td><?php echo $w['status']; ?></td>
                <td>
                  <?php if ($w['status'] === 'pending'): ?>
                  <form method='post' class='inline'>
                    <input type='hidden' name='withdraw_id' value='<?php echo $w['id']; ?>'>
                    <button name='action' value='approve' class='text-green-600 hover:underline'>✅</button>
                    <button name='action' value='reject' class='text-red-600 hover:underline ml-2'>❌</button>
                  </form>
                  <?php else: ?> -
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h2 class='text-xl font-semibold mb-2'>👤 รายชื่อสมาชิก</h2>
        <div class='bg-white p-4 rounded shadow h-96 overflow-y-scroll text-sm'>
          <table class='w-full'>
            <thead><tr><th>ชื่อผู้ใช้</th><th>รายได้</th><th>เติมเงิน</th><th>โบนัส</th></tr></thead>
            <tbody>
              <?php while($u = $users->fetch_assoc()): ?>
              <tr class='border-b'>
                <td><?php echo $u['username']; ?></td>
                <td>฿<?php echo $u['income'] ?? 0; ?></td>
                <td>฿<?php echo $u['topup'] ?? 0; ?></td>
                <td>฿<?php echo $u['bonus'] ?? 0; ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div class='mt-10'>
      <h2 class='text-xl font-semibold mb-2'>📢 แก้ไขประกาศหน้า Dashboard</h2>
      <form method='post'>
        <textarea name='announcement' rows='3' class='w-full border p-2 rounded'><?php echo htmlspecialchars($currentNotice); ?></textarea>
        <button type='submit' class='mt-2 bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700'>บันทึกประกาศ</button>
      </form>
    </div>

    <div class='mt-6 text-center'>
      <a href='../dashboard.php' class='text-sm text-indigo-500 hover:underline'>⬅ กลับหน้าหลัก</a>
    </div>
  </div>
</body>
</html>
