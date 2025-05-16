<?php
// ตรวจสอบการล็อกอิน และสิทธิ์แอดมิน
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php?redirect=admin/lottery_results.php');
    exit();
}

// เชื่อมต่อฐานข้อมูล
require_once('../config.php');
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset($db_config['charset']);

date_default_timezone_set('Asia/Bangkok');

// ฟังก์ชั่นแสดงข้อความแจ้งเตือน
function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// รวมไฟล์ header
include('header.php');

// ฟอร์มเพิ่มผลหวยใหม่ (เขียนต่อจากโค้ดเดิม)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_result'])) {
    $lottery_type = $_POST['lottery_type'];
    $draw_date = $_POST['draw_date'];
    $first_prize = $_POST['first_prize'];

    // เพิ่มข้อมูลผลหวย
    $sql = "INSERT INTO lottery_results (lottery_type, draw_date, first_prize, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $lottery_type, $draw_date, $first_prize);

    if ($stmt->execute()) {
        showAlert("บันทึกผลหวย $lottery_type วันที่ $draw_date สำเร็จ!");
    } else {
        showAlert("เกิดข้อผิดพลาดในการบันทึกผลหวย: " . $conn->error, 'danger');
    }

    // รีเฟรชหน้าเพื่อแสดงผลล่าสุด
    header("Location: lottery_results.php");
    exit();
}

// ดึงข้อมูลผลหวยทั้งหมด
$results_sql = "SELECT * FROM lottery_results ORDER BY draw_date DESC";
$results = $conn->query($results_sql);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <?php include('sidebar.php'); ?>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">จัดการผลหวย</h1>
            </div>
            
            <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert']); endif; ?>
            
            <!-- เพิ่มฟอร์มจัดการผลหวย -->
            <div class="card mb-4">
                <div class="card-header">
                    เพิ่มผลหวยใหม่
                </div>
                <div class="card-body">
                    <form method="POST" action="lottery_results.php">
                        <div class="mb-3">
                            <label for="lottery_type" class="form-label">ประเภทหวย</label>
                            <input type="text" class="form-control" name="lottery_type" id="lottery_type" required>
                        </div>
                        <div class="mb-3">
                            <label for="draw_date" class="form-label">วันที่ออกผล</label>
                            <input type="date" class="form-control" name="draw_date" id="draw_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_prize" class="form-label">รางวัลที่ 1</label>
                            <input type="text" class="form-control" name="first_prize" id="first_prize" required>
                        </div>
                        <button type="submit" name="save_result" class="btn btn-primary">บันทึกผลหวย</button>
                    </form>
                </div>
            </div>
            
            <!-- ตารางแสดงผลหวย -->
            <div class="card">
                <div class="card-header">
                    รายการผลหวยทั้งหมด
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ประเภทหวย</th>
                                <th>วันที่ออก</th>
                                <th>รางวัลที่ 1</th>
                                <th>วันที่สร้าง</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $results->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['lottery_type']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['draw_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['first_prize']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="lottery_results.php?edit=1&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                    <a href="lottery_results.php?delete=1&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')">ลบ</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
