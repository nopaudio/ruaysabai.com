<?php
// ตรวจสอบการเข้าสู่ระบบและสิทธิ์แอดมิน
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once('../config.php');
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8
$conn->set_charset($db_config['charset']);

// รับค่า deposit_id จาก URL
$deposit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ดึงข้อมูลการเติมเงิน
$deposit = null;
if ($deposit_id > 0) {
    $stmt = $conn->prepare("SELECT d.*, u.username, u.phone FROM deposits d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->bind_param("i", $deposit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $deposit = $result->fetch_assoc();
    } else {
        echo "ไม่พบข้อมูลการเติมเงิน";
        exit;
    }
    $stmt->close();
}

// ดำเนินการตามการกระทำ (อนุมัติหรือปฏิเสธ)
if ($action && $deposit_id) {
    if ($action === 'approve') {
        // เริ่ม transaction
        $conn->begin_transaction();
        
        try {
            // อัปเดตสถานะการเติมเงินเป็น 'approved'
            $stmt = $conn->prepare("UPDATE deposits SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
            $stmt->bind_param("ii", $_SESSION['user_id'], $deposit_id);
            $stmt->execute();
            
            // เพิ่มเงินให้กับผู้ใช้
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $deposit['amount'], $deposit['user_id']);
            $stmt->execute();
            
            // บันทึกประวัติการทำรายการ
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, created_at) VALUES (?, 'deposit', ?, ?, NOW())");
            $description = "เติมเงินผ่าน " . $deposit['payment_method'] . " - อนุมัติโดยแอดมิน";
            $stmt->bind_param("ids", $deposit['user_id'], $deposit['amount'], $description);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // แสดงข้อความสำเร็จและ redirect กลับไปยังหน้ารายการเติมเงิน
            echo "<script>alert('อนุมัติการเติมเงินเรียบร้อยแล้ว'); window.location='deposits.php';</script>";
            exit;
        } catch (Exception $e) {
            // ถ้ามีข้อผิดพลาด roll back
            $conn->rollback();
            echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "'); window.location='deposits.php';</script>";
            exit;
        }
    } elseif ($action === 'reject') {
        // อัปเดตสถานะการเติมเงินเป็น 'rejected'
        $stmt = $conn->prepare("UPDATE deposits SET status = 'rejected', approved_at = NOW(), approved_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $deposit_id);
        $stmt->execute();
        
        echo "<script>alert('ปฏิเสธการเติมเงินเรียบร้อยแล้ว'); window.location='deposits.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบการเติมเงิน - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Menu -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">ตรวจสอบการเติมเงิน</h1>
                </div>
                
                <?php if ($deposit): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">รายละเอียดการเติมเงิน #<?php echo $deposit['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ผู้ใช้:</strong> <?php echo htmlspecialchars($deposit['username']); ?></p>
                                <p><strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($deposit['phone']); ?></p>
                                <p><strong>จำนวนเงิน:</strong> <?php echo number_format($deposit['amount'], 2); ?> บาท</p>
                                <p><strong>วันที่เติมเงิน:</strong> <?php echo date('d/m/Y H:i:s', strtotime($deposit['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>ช่องทางการชำระเงิน:</strong> <?php echo htmlspecialchars($deposit['payment_method']); ?></p>
                                <p><strong>สถานะ:</strong> 
                                    <span class="badge bg-<?php 
                                        echo ($deposit['status'] == 'pending') ? 'warning' : 
                                            (($deposit['status'] == 'approved') ? 'success' : 'danger'); 
                                    ?>">
                                        <?php 
                                            echo ($deposit['status'] == 'pending') ? 'รอดำเนินการ' : 
                                                (($deposit['status'] == 'approved') ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว'); 
                                        ?>
                                    </span>
                                </p>
                                <p><strong>รายละเอียด:</strong> <?php echo nl2br(htmlspecialchars($deposit['notes'])); ?></p>
                                <?php if (!empty($deposit['transfer_slip'])): ?>
                                    <p><strong>สลิปการโอนเงิน:</strong></p>
                                    <img src="../<?php echo htmlspecialchars($deposit['transfer_slip']); ?>" alt="สลิปการโอนเงิน" class="img-fluid mb-3" style="max-height: 300px;">
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($deposit['status'] == 'pending'): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <a href="verify_deposit.php?id=<?php echo $deposit['id']; ?>&action=approve" class="btn btn-success me-2" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะอนุมัติการเติมเงินนี้?');">
                                <i class="fas fa-check"></i> อนุมัติ
                            </a>
                            <a href="verify_deposit.php?id=<?php echo $deposit['id']; ?>&action=reject" class="btn btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะปฏิเสธการเติมเงินนี้?');">
                                <i class="fas fa-times"></i> ปฏิเสธ
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">ไม่พบข้อมูลการเติมเงิน หรือข้อมูลไม่ถูกต้อง</div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <a href="deposits.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับไปยังรายการเติมเงิน
                    </a>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
