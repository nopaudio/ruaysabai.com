<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// จัดการการยืนยันสมาชิก VIP
if (isset($_GET['action']) && $_GET['action'] == 'approve_vip' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // อัพเดทสถานะเป็น VIP
    $stmt = $conn->prepare("UPDATE users SET is_vip = 1, vip_start_date = NOW(), vip_expire_date = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // ดึงข้อมูลการชำระเงินแล้วอัพเดทสถานะ
    $stmt = $conn->prepare("UPDATE payments SET status = 'approved' WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    header('Location: members.php?msg=approved');
    exit();
}

// จัดการการปฏิเสธสมาชิก VIP
if (isset($_GET['action']) && $_GET['action'] == 'reject_vip' && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // อัพเดทสถานะการชำระเงิน
    $stmt = $conn->prepare("UPDATE payments SET status = 'rejected' WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    header('Location: members.php?msg=rejected');
    exit();
}

// ดึงข้อมูลสมาชิกทั้งหมด
$members = $conn->query("SELECT u.*, p.amount, p.payment_proof, p.status as payment_status, p.payment_date
                        FROM users u
                        LEFT JOIN payments p ON u.id = p.user_id AND p.status = 'pending'
                        ORDER BY u.created_at DESC");

// ดึงข้อมูลสมาชิกที่รอการอนุมัติ VIP
$pending_vip = $conn->query("SELECT u.*, p.amount, p.payment_proof, p.payment_date
                            FROM users u
                            JOIN payments p ON u.id = p.user_id
                            WHERE p.status = 'pending'
                            ORDER BY p.payment_date DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-black bg-opacity-50 backdrop-blur-md p-4">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center mb-4 md:mb-0">
                <i class="fas fa-crown text-yellow-400 text-2xl mr-2"></i>
                <h1 class="text-2xl font-bold text-yellow-400">Admin Panel</h1>
            </div>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="dashboard.php" class="text-gray-300 hover:text-yellow-400 transition duration-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>แดชบอร์ด
                </a>
                <a href="members.php" class="text-yellow-400">
                    <i class="fas fa-users mr-2"></i>จัดการสมาชิก
                </a>
                <a href="vip-approvals.php" class="text-gray-300 hover:text-yellow-400 transition duration-300">
                    <i class="fas fa-check-circle mr-2"></i>อนุมัติ VIP
                </a>
                <a href="rewards.php" class="text-gray-300 hover:text-yellow-400 transition duration-300">
                    <i class="fas fa-gift mr-2"></i>จัดการรางวัล
                </a>
                <a href="logout.php" class="text-red-400 hover:text-red-300 transition duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-4 p-4 rounded-lg <?= $_GET['msg'] == 'approved' ? 'bg-green-600' : 'bg-red-600' ?> text-white">
                <?= $_GET['msg'] == 'approved' ? 'อนุมัติสมาชิก VIP สำเร็จ!' : 'ปฏิเสธคำขอสมาชิก VIP แล้ว' ?>
            </div>
        <?php endif; ?>

        <!-- สมาชิกที่รอการอนุมัติ VIP -->
        <?php if ($pending_vip->num_rows > 0): ?>
        <div class="bg-black bg-opacity-70 backdrop-blur-md rounded-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-yellow-400">
                    <i class="fas fa-clock mr-2"></i>รออนุมัติ VIP
                </h2>
                <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                    <?= $pending_vip->num_rows ?> รายการ
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-white">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="py-3 px-4 text-left">ชื่อผู้ใช้</th>
                            <th class="py-3 px-4 text-left">อีเมล</th>
                            <th class="py-3 px-4 text-left">จำนวนเงิน</th>
                            <th class="py-3 px-4 text-left">วันที่ชำระ</th>
                            <th class="py-3 px-4 text-left">หลักฐาน</th>
                            <th class="py-3 px-4 text-left">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $pending_vip->fetch_assoc()): ?>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4"><?= htmlspecialchars($member['username']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($member['email']) ?></td>
                            <td class="py-3 px-4"><?= number_format($member['amount']) ?> บาท</td>
                            <td class="py-3 px-4"><?= date('d/m/Y H:i', strtotime($member['payment_date'])) ?></td>
                            <td class="py-3 px-4">
                                <a href="<?= $member['payment_proof'] ?>" target="_blank" class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-image"></i> ดูสลิป
                                </a>
                            </td>
                            <td class="py-3 px-4">
                                <a href="?action=approve_vip&id=<?= $member['id'] ?>" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded mr-2"
                                   onclick="return confirm('ยืนยันการอนุมัติ VIP?')">
                                    <i class="fas fa-check"></i> อนุมัติ
                                </a>
                                <a href="?action=reject_vip&id=<?= $member['id'] ?>" 
                                   class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded"
                                   onclick="return confirm('ยืนยันการปฏิเสธ?')">
                                    <i class="fas fa-times"></i> ปฏิเสธ
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- รายชื่อสมาชิกทั้งหมด -->
        <div class="bg-black bg-opacity-70 backdrop-blur-md rounded-lg p-6">
            <h2 class="text-2xl font-bold text-yellow-400 mb-4">
                <i class="fas fa-users mr-2"></i>สมาชิกทั้งหมด
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-white">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="py-3 px-4 text-left">ID</th>
                            <th class="py-3 px-4 text-left">ชื่อผู้ใช้</th>
                            <th class="py-3 px-4 text-left">อีเมล</th>
                            <th class="py-3 px-4 text-left">ประเภท</th>
                            <th class="py-3 px-4 text-left">แต้ม</th>
                            <th class="py-3 px-4 text-left">VIP หมดอายุ</th>
                            <th class="py-3 px-4 text-left">วันที่สมัคร</th>
                            <th class="py-3 px-4 text-left">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($member = $members->fetch_assoc()): ?>
                        <tr class="border-b border-gray-800">
                            <td class="py-3 px-4"><?= $member['id'] ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($member['username']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($member['email']) ?></td>
                            <td class="py-3 px-4">
                                <?php if ($member['is_vip']): ?>
                                    <span class="bg-yellow-600 text-white px-2 py-1 rounded text-sm">VIP</span>
                                <?php else: ?>
                                    <span class="bg-gray-600 text-white px-2 py-1 rounded text-sm">ทั่วไป</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4"><?= number_format($member['points']) ?></td>
                            <td class="py-3 px-4">
                                <?= $member['vip_expire_date'] ? date('d/m/Y', strtotime($member['vip_expire_date'])) : '-' ?>
                            </td>
                            <td class="py-3 px-4"><?= date('d/m/Y', strtotime($member['created_at'])) ?></td>
                            <td class="py-3 px-4">
                                <a href="member-detail.php?id=<?= $member['id'] ?>" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                    <i class="fas fa-eye"></i> ดูข้อมูล
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>