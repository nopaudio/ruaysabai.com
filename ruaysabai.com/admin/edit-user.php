<?php
// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // ถ้าไม่ได้ล็อกอินหรือไม่ใช่แอดมิน ให้ redirect ไปหน้า login
    $_SESSION['redirect_after_login'] = '/admin/';
    $_SESSION['login_message'] = 'กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ';
    header('Location: ../login.php');
    exit;
}

// นำเข้าไฟล์การตั้งค่า
require_once '../config.php';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$userId = $_GET['id'];

// ตรวจสอบการบันทึกข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $balance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0;
    
    // ตรวจสอบรหัสผ่านใหม่
    $newPassword = $_POST['new_password'] ?? '';
    
    try {
        $db = connectDB();
        
        // เตรียมคำสั่ง SQL
        if (!empty($newPassword)) {
            // กรณีเปลี่ยนรหัสผ่าน
            $hashedPassword = hashPassword($newPassword);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ?, is_admin = ?, balance = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $status, $is_admin, $balance, $hashedPassword, $userId]);
        } else {
            // กรณีไม่เปลี่ยนรหัสผ่าน
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ?, is_admin = ?, balance = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $status, $is_admin, $balance, $userId]);
        }
        
        $_SESSION['admin_message'] = "อัปเดตข้อมูลผู้ใช้เรียบร้อย";
        header('Location: users.php');
        exit;
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
    }
}

// ดึงข้อมูลผู้ใช้
try {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['admin_message'] = "ไม่พบข้อมูลผู้ใช้";
        header('Location: users.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้งาน - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- เมนูด้านข้าง -->
        <?php include 'sidebar.php'; ?>
        
        <!-- เนื้อหาหลัก -->
        <div class="admin-content">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <h1>แก้ไขผู้ใช้งาน</h1>
            
            <div class="card">
                <h2 class="card-title">แก้ไขข้อมูลผู้ใช้: <?php echo htmlspecialchars($user['username']); ?></h2>
                
                <form method="POST" action="" class="form-horizontal">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้:</label>
                        <div class="form-control-wrap">
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            <div class="form-text">ไม่สามารถแก้ไขชื่อผู้ใช้ได้</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">ชื่อ-นามสกุล:</label>
                        <div class="form-control-wrap">
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">อีเมล:</label>
                        <div class="form-control-wrap">
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์:</label>
                        <div class="form-control-wrap">
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="balance">ยอดเงิน (บาท):</label>
                        <div class="form-control-wrap">
                            <input type="number" id="balance" name="balance" class="form-control" value="<?php echo $user['balance']; ?>" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">สถานะ:</label>
                        <div class="form-control-wrap">
                            <select id="status" name="status" class="form-control" required>
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>ใช้งาน</option>
                                <option value="blocked" <?php echo $user['status'] == 'blocked' ? 'selected' : ''; ?>>ระงับ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="is_admin">สิทธิ์การใช้งาน:</label>
                        <div class="form-control-wrap" style="padding-top: 0.5rem;">
                            <div class="form-check">
                                <input type="checkbox" id="is_admin" name="is_admin" class="form-check-input" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                                <label for="is_admin" class="form-check-label">ผู้ดูแลระบบ (แอดมิน)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">รหัสผ่านใหม่:</label>
                        <div class="form-control-wrap">
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_user" class="btn btn-accent">บันทึกข้อมูล</button>
                        <a href="users.php" class="btn">ยกเลิก</a>
                    </div>
                </form>
            </div>
            
            <!-- ประวัติการเติมเงิน -->
            <div class="card">
                <h2 class="card-title">ประวัติการเติมเงิน</h2>
                
                <?php
                try {
                    $stmt = $db->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                    $stmt->execute([$userId]);
                    $deposits = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    $deposits = [];
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>วันที่</th>
                                <th>วิธีการเติมเงิน</th>
                                <th>จำนวนเงิน</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">ไม่มีประวัติการเติมเงิน</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td><?php echo $deposit['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($deposit['created_at'])); ?></td>
                                    <td><?php echo $payment_methods[$deposit['method']]; ?></td>
                                    <td><?php echo number_format($deposit['amount'], 2); ?> บาท</td>
                                    <td>
                                        <span class="badge <?php echo getStatusClass($deposit['status']); ?>">
                                            <?php echo getStatusText($deposit['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer">
                    <a href="deposits.php?user_id=<?php echo $userId; ?>" class="btn">ดูประวัติทั้งหมด</a>
                </div>
            </div>
            
            <!-- ประวัติการซื้อหวย -->
            <div class="card">
                <h2 class="card-title">ประวัติการซื้อหวย</h2>
                
                <?php
                try {
                    $stmt = $db->prepare("SELECT p.*, l.date as lottery_date FROM purchases p JOIN lotteries l ON p.lottery_id = l.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 10");
                    $stmt->execute([$userId]);
                    $purchases = $stmt->fetchAll();
                } catch (PDOException $e) {
                    error_log("Database error: " . $e->getMessage());
                    $purchases = [];
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>งวดวันที่</th>
                                <th>ประเภท</th>
                                <th>เลข</th>
                                <th>จำนวนเงิน</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">ไม่มีประวัติการซื้อหวย</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td><?php echo $purchase['id']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($purchase['lottery_date'])); ?></td>
                                    <td>
                                        <?php 
                                        switch ($purchase['lottery_type']) {
                                            case 'firstPrize':
                                                echo 'รางวัลที่ 1';
                                                break;
                                            case 'frontThree':
                                                echo 'เลขหน้า 3 ตัว';
                                                break;
                                            case 'backThree':
                                                echo 'เลขท้าย 3 ตัว';
                                                break;
                                            case 'backTwo':
                                                echo 'เลขท้าย 2 ตัว';
                                                break;
                                            default:
                                                echo $purchase['lottery_type'];
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $purchase['number']; ?></td>
                                    <td><?php echo number_format($purchase['amount'], 2); ?> บาท</td>
                                    <td>
                                        <span class="badge <?php echo getStatusClass($purchase['status'], 'lottery'); ?>">
                                            <?php echo getStatusText($purchase['status'], 'lottery'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer">
                    <a href="purchases.php?user_id=<?php echo $userId; ?>" class="btn">ดูประวัติทั้งหมด</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>