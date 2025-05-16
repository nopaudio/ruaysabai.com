<?php
session_start();
require_once '../config.php';
require_once 'admin_auth.php'; // ตรวจสอบสิทธิ์ของผู้ดูแลระบบ

// จัดการการส่งค่าฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // การจัดการเมื่อมีการส่งฟอร์มออกผลหวย
    if (isset($_POST['action']) && $_POST['action'] === 'add_result') {
        $lottery_type = $_POST['lottery_type'];
        $draw_date = $_POST['draw_date'];
        $first_prize = $_POST['first_prize'];
        $last_two_digits = $_POST['last_two_digits'];
        $first_three_digits_front = $_POST['first_three_digits_front'];
        $first_three_digits_back = $_POST['first_three_digits_back'];
        $created_by = $_SESSION['admin_id'];
        
        try {
            // เช็คว่ามีผลหวยของวันที่ที่กำหนดไว้แล้วหรือไม่
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM lottery_results WHERE lottery_type = ? AND draw_date = ?");
            $check_stmt->bind_param("ss", $lottery_type, $draw_date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $exists = $check_result->fetch_row()[0];
            
            if ($exists > 0) {
                // อัพเดทผลหวยที่มีอยู่แล้ว
                $update_stmt = $conn->prepare("UPDATE lottery_results SET 
                    first_prize = ?, 
                    last_two_digits = ?, 
                    first_three_digits_front = ?, 
                    first_three_digits_back = ?, 
                    updated_at = NOW(),
                    updated_by = ?
                    WHERE lottery_type = ? AND draw_date = ?");
                
                $update_stmt->bind_param("sssssss", 
                    $first_prize, 
                    $last_two_digits, 
                    $first_three_digits_front, 
                    $first_three_digits_back, 
                    $created_by,
                    $lottery_type,
                    $draw_date
                );
                
                if ($update_stmt->execute()) {
                    // ประมวลผลการถูกรางวัล
                    process_winning_tickets($lottery_type, $draw_date);
                    $_SESSION['success_message'] = "อัพเดทผลหวยเรียบร้อยแล้ว";
                } else {
                    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัพเดทผลหวย: " . $conn->error;
                }
            } else {
                // เพิ่มผลหวยใหม่
                $insert_stmt = $conn->prepare("INSERT INTO lottery_results 
                    (lottery_type, draw_date, first_prize, last_two_digits, first_three_digits_front, first_three_digits_back, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $insert_stmt->bind_param("ssssssi", 
                    $lottery_type, 
                    $draw_date, 
                    $first_prize, 
                    $last_two_digits, 
                    $first_three_digits_front, 
                    $first_three_digits_back, 
                    $created_by
                );
                
                if ($insert_stmt->execute()) {
                    // ประมวลผลการถูกรางวัล
                    process_winning_tickets($lottery_type, $draw_date);
                    $_SESSION['success_message'] = "เพิ่มผลหวยเรียบร้อยแล้ว";
                } else {
                    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเพิ่มผลหวย: " . $conn->error;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
        
        // ไปยังหน้าผลหวย
        header('Location: lottery_results.php');
        exit();
    }
    
    // การจัดการเมื่อมีการส่งฟอร์มลบผลหวย
    if (isset($_POST['action']) && $_POST['action'] === 'delete_result') {
        $result_id = $_POST['result_id'];
        
        try {
            // ลบผลหวย
            $delete_stmt = $conn->prepare("DELETE FROM lottery_results WHERE id = ?");
            $delete_stmt->bind_param("i", $result_id);
            
            if ($delete_stmt->execute()) {
                // ต้องเคลียร์ผลการชนะด้วย
                $reset_stmt = $conn->prepare("UPDATE lottery_tickets SET 
                    is_checked = 0, 
                    is_winner = 0, 
                    winning_amount = 0, 
                    checked_at = NULL 
                    WHERE result_id = ?");
                $reset_stmt->bind_param("i", $result_id);
                $reset_stmt->execute();
                
                $_SESSION['success_message'] = "ลบผลหวยเรียบร้อยแล้ว";
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบผลหวย: " . $conn->error;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
        
        // ไปยังหน้าผลหวย
        header('Location: lottery_results.php');
        exit();
    }
}

// ฟังก์ชันประมวลผลการถูกรางวัล
function process_winning_tickets($lottery_type, $draw_date) {
    global $conn;
    
    // ดึงข้อมูลผลหวยล่าสุด
    $result_stmt = $conn->prepare("SELECT * FROM lottery_results WHERE lottery_type = ? AND draw_date = ?");
    $result_stmt->bind_param("ss", $lottery_type, $draw_date);
    $result_stmt->execute();
    $result_data = $result_stmt->get_result()->fetch_assoc();
    
    if (!$result_data) {
        return false;
    }
    
    $result_id = $result_data['id'];
    $first_prize = $result_data['first_prize'];
    $last_two_digits = $result_data['last_two_digits'];
    $first_three_digits_front = $result_data['first_three_digits_front'];
    $first_three_digits_back = $result_data['first_three_digits_back'];
    
    // ดึงข้อมูลตั๋วหวยที่ยังไม่ได้ตรวจ
    $tickets_stmt = $conn->prepare("SELECT * FROM lottery_tickets 
                                   WHERE lottery_type = ? 
                                   AND draw_date = ? 
                                   AND status = 'active' 
                                   AND is_checked = 0");
    $tickets_stmt->bind_param("ss", $lottery_type, $draw_date);
    $tickets_stmt->execute();
    $tickets_result = $tickets_stmt->get_result();
    
    // ดึงอัตราการจ่าย
    $payout_rates = get_payout_rates();
    
    while ($ticket = $tickets_result->fetch_assoc()) {
        $is_winner = false;
        $winning_amount = 0;
        $winning_type = '';
        
        // ตรวจรางวัลตามประเภทการแทง
        switch ($ticket['bet_type']) {
            case 'three_digits_front':
                if ($ticket['number'] === $first_three_digits_front) {
                    $is_winner = true;
                    $winning_amount = $ticket['amount'] * $payout_rates['three_digits_front'];
                    $winning_type = 'three_digits_front';
                }
                break;
                
            case 'three_digits_back':
                if ($ticket['number'] === $first_three_digits_back) {
                    $is_winner = true;
                    $winning_amount = $ticket['amount'] * $payout_rates['three_digits_back'];
                    $winning_type = 'three_digits_back';
                }
                break;
                
            case 'two_digits':
                if ($ticket['number'] === $last_two_digits) {
                    $is_winner = true;
                    $winning_amount = $ticket['amount'] * $payout_rates['two_digits'];
                    $winning_type = 'two_digits';
                }
                break;
                
            case 'run_top':
                // เลขวิ่งบน ตรวจกับเลขสามตัวหน้า
                if (strpos($first_three_digits_front, $ticket['number']) !== false) {
                    $is_winner = true;
                    $winning_amount = $ticket['amount'] * $payout_rates['run_top'];
                    $winning_type = 'run_top';
                }
                break;
                
            case 'run_bottom':
                // เลขวิ่งล่าง ตรวจกับเลขสองตัวล่าง
                if (strpos($last_two_digits, $ticket['number']) !== false) {
                    $is_winner = true;
                    $winning_amount = $ticket['amount'] * $payout_rates['run_bottom'];
                    $winning_type = 'run_bottom';
                }
                break;
        }
        
        // อัพเดทสถานะการตรวจรางวัล
        $update_ticket = $conn->prepare("UPDATE lottery_tickets SET 
                                        is_checked = 1, 
                                        is_winner = ?, 
                                        winning_amount = ?, 
                                        winning_type = ?,
                                        result_id = ?,
                                        checked_at = NOW() 
                                        WHERE id = ?");
        $update_ticket->bind_param("idsii", 
                                  $is_winner, 
                                  $winning_amount, 
                                  $winning_type,
                                  $result_id,
                                  $ticket['id']);
        $update_ticket->execute();
        
        // ถ้าถูกรางวัล ให้เพิ่มเงินเข้าบัญชีผู้ใช้
        if ($is_winner) {
            $update_wallet = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update_wallet->bind_param("di", $winning_amount, $ticket['user_id']);
            $update_wallet->execute();
            
            // บันทึกประวัติการเพิ่มเงิน
            $transaction_stmt = $conn->prepare("INSERT INTO transactions 
                                              (user_id, amount, type, status, description) 
                                              VALUES (?, ?, 'winning', 'completed', ?)");
            $description = "ถูกรางวัล {$winning_type} หวย{$lottery_type} งวดวันที่ {$draw_date}";
            $transaction_stmt->bind_param("ids", $ticket['user_id'], $winning_amount, $description);
            $transaction_stmt->execute();
        }
    }
    
    return true;
}

// ฟังก์ชันดึงอัตราการจ่าย
function get_payout_rates() {
    global $conn;
    
    $rates = [
        'three_digits_front' => 500,  // ค่าเริ่มต้น
        'three_digits_back' => 500,   // ค่าเริ่มต้น
        'two_digits' => 90,           // ค่าเริ่มต้น
        'run_top' => 3,               // ค่าเริ่มต้น
        'run_bottom' => 4             // ค่าเริ่มต้น
    ];
    
    // ดึงค่าจากฐานข้อมูล
    $stmt = $conn->prepare("SELECT setting_name, setting_value FROM settings WHERE setting_group = 'payout_rates'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $setting_name = $row['setting_name'];
        if (array_key_exists($setting_name, $rates)) {
            $rates[$setting_name] = floatval($row['setting_value']);
        }
    }
    
    return $rates;
}

// ดึงรายการผลหวยทั้งหมด เรียงตามวันที่ล่าสุด
$query = "SELECT * FROM lottery_results ORDER BY draw_date DESC";
$result = $conn->query($query);
$lottery_results = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lottery_results[] = $row;
    }
}

// ดึงรายการประเภทหวย
$lottery_types = ['หวยรัฐบาล', 'หวยหุ้นไทย', 'หวยลาว', 'หวยฮานอย', 'หวยยี่กี'];

// แสดงผลข้อความเมื่อมีการกระทำ
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// ล้างข้อความ
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// หัวเรื่องหน้า
$page_title = "จัดการผลหวย";
?>

<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">จัดการผลหวย</h1>
            </div>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- แบบฟอร์มเพิ่ม/แก้ไขผลหวย -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> เพิ่มผลหวย
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="addResultForm">
                        <input type="hidden" name="action" value="add_result">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="lottery_type" class="form-label">ประเภทหวย</label>
                                <select class="form-select" id="lottery_type" name="lottery_type" required>
                                    <?php foreach ($lottery_types as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="draw_date" class="form-label">วันที่ออกรางวัล</label>
                                <input type="date" class="form-control" id="draw_date" name="draw_date" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="first_prize" class="form-label">รางวัลที่ 1</label>
                                <input type="text" class="form-control" id="first_prize" name="first_prize" pattern="[0-9]{6}" maxlength="6" placeholder="ตัวเลข 6 หลัก" required>
                            </div>
                            <div class="col-md-3">
                                <label for="last_two_digits" class="form-label">เลขท้าย 2 ตัว</label>
                                <input type="text" class="form-control" id="last_two_digits" name="last_two_digits" pattern="[0-9]{2}" maxlength="2" placeholder="ตัวเลข 2 หลัก" required>
                            </div>
                            <div class="col-md-3">
                                <label for="first_three_digits_front" class="form-label">เลขหน้า 3 ตัว</label>
                                <input type="text" class="form-control" id="first_three_digits_front" name="first_three_digits_front" pattern="[0-9]{3}" maxlength="3" placeholder="ตัวเลข 3 หลัก" required>
                            </div>
                            <div class="col-md-3">
                                <label for="first_three_digits_back" class="form-label">เลขท้าย 3 ตัว</label>
                                <input type="text" class="form-control" id="first_three_digits_back" name="first_three_digits_back" pattern="[0-9]{3}" maxlength="3" placeholder="ตัวเลข 3 หลัก" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">บันทึกผลหวย</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- ตารางแสดงผลหวย -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table"></i> รายการผลหวย
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ประเภทหวย</th>
                                    <th>วันที่ออก</th>
                                    <th>รางวัลที่ 1</th>
                                    <th>เลขท้าย 2 ตัว</th>
                                    <th>เลขหน้า 3 ตัว</th>
                                    <th>เลขท้าย 3 ตัว</th>
                                    <th>วันที่เพิ่ม</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lottery_results) > 0): ?>
                                    <?php foreach ($lottery_results as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['lottery_type']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($result['draw_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($result['first_prize']); ?></td>
                                        <td><?php echo htmlspecialchars($result['last_two_digits']); ?></td>
                                        <td><?php echo htmlspecialchars($result['first_three_digits_front']); ?></td>
                                        <td><?php echo htmlspecialchars($result['first_three_digits_back']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($result['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-result" 
                                                    data-id="<?php echo $result['id']; ?>"
                                                    data-type="<?php echo $result['lottery_type']; ?>"
                                                    data-date="<?php echo $result['draw_date']; ?>"
                                                    data-first="<?php echo $result['first_prize']; ?>"
                                                    data-last-two="<?php echo $result['last_two_digits']; ?>"
                                                    data-front-three="<?php echo $result['first_three_digits_front']; ?>"
                                                    data-back-three="<?php echo $result['first_three_digits_back']; ?>">
                                                แก้ไข
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-result" data-id="<?php echo $result['id']; ?>">ลบ</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">ไม่พบข้อมูลผลหวย</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal สำหรับยืนยันการลบ -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                คุณต้องการลบผลหวยนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_result">
                    <input type="hidden" name="result_id" id="deleteResultId" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datatable initialization
    $('#dataTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
        },
        "order": [[1, 'desc']] // เรียงตามวันที่ล่าสุด
    });
    
    // Set today as default date
    document.getElementById('draw_date').valueAsDate = new Date();
    
    // Edit result button
    const editButtons = document.querySelectorAll('.edit-result');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lotteryType = this.getAttribute('data-type');
            const drawDate = this.getAttribute('data-date');
            const firstPrize = this.getAttribute('data-first');
            const lastTwo = this.getAttribute('data-last-two');
            const frontThree = this.getAttribute('data-front-three');
            const backThree = this.getAttribute('data-back-three');
            
            // Set form values
            document.getElementById('lottery_type').value = lotteryType;
            document.getElementById('draw_date').value = drawDate;
            document.getElementById('first_prize').value = firstPrize;
            document.getElementById('last_two_digits').value = lastTwo;
            document.getElementById('first_three_digits_front').value = frontThree;
            document.getElementById('first_three_digits_back').value = backThree;
            
            // Scroll to form
            document.getElementById('addResultForm').scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Delete result button
    const deleteButtons = document.querySelectorAll('.delete-result');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const resultId = this.getAttribute('data-id');
            document.getElementById('deleteResultId').value = resultId;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        });
    });
});
</script>
