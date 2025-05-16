<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/notifications.php';
require_once 'includes/admin_auth.php'; // ตรวจสอบการเข้าสู่ระบบของแอดมิน

$page_title = "จัดการผลรางวัล";

// ฟังก์ชันตรวจสอบและจ่ายเงินรางวัล
function processWinnings($lottery_id, $draw_date, $result_numbers) {
    global $conn;
    
    // 1. ดึงข้อมูลการซื้อหวยสำหรับงวดนี้
    $stmt = $conn->prepare("SELECT * FROM lottery_tickets WHERE lottery_id = ? AND status = 'active'");
    $stmt->bind_param("i", $lottery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tickets = $result->fetch_all(MYSQLI_ASSOC);
    
    // 2. ดึงอัตราการจ่ายจากระบบ
    $rates = getLotteryPayoutRates();
    
    // 3. ตรวจสอบเลขที่ถูกรางวัล
    $winners_count = 0;
    $total_payout = 0;
    
    foreach ($tickets as $ticket) {
        $user_id = $ticket['user_id'];
        $ticket_id = $ticket['id'];
        $numbers = $ticket['numbers'];
        $amount = $ticket['amount'];
        $winning_type = null;
        $winning_amount = 0;
        
        // ตรวจสอบประเภทรางวัล
        if ($numbers == $result_numbers['first_prize']) {
            // รางวัลที่ 1
            $winning_type = 'first_prize';
            $winning_amount = $amount * $rates['first_prize'];
        } elseif (substr($numbers, -2) == $result_numbers['last_two']) {
            // 2 ตัวท้าย
            $winning_type = 'last_two';
            $winning_amount = $amount * $rates['last_two'];
        } elseif (substr($numbers, 0, 3) == $result_numbers['first_three']) {
            // 3 ตัวหน้า
            $winning_type = 'first_three';
            $winning_amount = $amount * $rates['first_three'];
        } elseif (substr($numbers, -3) == $result_numbers['last_three']) {
            // 3 ตัวท้าย
            $winning_type = 'last_three';
            $winning_amount = $amount * $rates['last_three'];
        }
        
        // ถ้าถูกรางวัล
        if ($winning_type) {
            // บันทึกข้อมูลการถูกรางวัล
            $stmt = $conn->prepare("INSERT INTO lottery_winnings (user_id, ticket_id, lottery_id, winning_type, 
                                   amount, winning_amount, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiisdd", $user_id, $ticket_id, $lottery_id, $winning_type, $amount, $winning_amount);
            $stmt->execute();
            
            // อัปเดตสถานะตั๋ว
            $stmt = $conn->prepare("UPDATE lottery_tickets SET status = 'won', payout_amount = ? WHERE id = ?");
            $stmt->bind_param("di", $winning_amount, $ticket_id);
            $stmt->execute();
            
            // เพิ่มเงินให้ผู้ใช้
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $winning_amount, $user_id);
            $stmt->execute();
            
            // บันทึกธุรกรรม
            addTransaction($user_id, 'winning', $winning_amount, "ถูกรางวัล $winning_type จากเลข $numbers");
            
            // ส่งการแจ้งเตือน
            notifyWinning($user_id, $numbers, $winning_amount);
            
            $winners_count++;
            $total_payout += $winning_amount;
        } else {
            // อัปเดตสถานะตั๋วว่าไม่ถูกรางวัล
            $stmt = $conn->prepare("UPDATE lottery_tickets SET status = 'lost' WHERE id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
        }
    }
    
    // แจ้งเตือนทุกคนว่ามีการออกผลรางวัลแล้ว
    notifyAllUsersAboutResult($draw_date);
    
    return [
        'winners_count' => $winners_count,
        'total_payout' => $total_payout
    ];
}

// ฟังก์ชันแจ้งเตือนผู้ใช้ทุกคนเกี่ยวกับผลรางวัล
function notifyAllUsersAboutResult($draw_date) {
    global $conn;
    
    $result = $conn->query("SELECT id FROM users WHERE status = 'active'");
    while ($row = $result->fetch_assoc()) {
        notifyLotteryResult($row['id'], $draw_date);
    }
}

// ฟังก์ชันดึงอัตราการจ่ายรางวัล
function getLotteryPayoutRates() {
    global $conn;
    
    $rates = [
        'first_prize' => 500,    // รางวัลที่ 1 บาทละ 500 บาท
        'last_two' => 70,        // 2 ตัวท้าย บาทละ 70 บาท
        'first_three' => 500,    // 3 ตัวหน้า บาทละ 500 บาท
        'last_three' => 500      // 3 ตัวท้าย บาทละ 500 บาท
    ];
    
    $result = $conn->query("SELECT * FROM settings WHERE category = 'payout_rate'");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rates[$row['key']] = floatval($row['value']);
        }
    }
    
    return $rates;
}

// ตรวจสอบว่ามีการส่งฟอร์มออกผลรางวัล
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_result'])) {
    $lottery_id = $_POST['lottery_id'];
    $draw_date = $_POST['draw_date'];
    $first_prize = $_POST['first_prize'];
    $last_two = $_POST['last_two'];
    $first_three = $_POST['first_three'];
    $last_three = $_POST['last_three'];
    
    // ตรวจสอบความถูกต้องของข้อมูล
    $errors = [];
    
    if (strlen($first_prize) != 6 || !is_numeric($first_prize)) {
        $errors[] = "รางวัลที่ 1 ต้องเป็นตัวเลข 6 หลัก";
    }
    
    if (strlen($last_two) != 2 || !is_numeric($last_two)) {
        $errors[] = "2 ตัวท้ายต้องเป็นตัวเลข 2 หลัก";
    }
    
    if (strlen($first_three) != 3 || !is_numeric($first_three)) {
        $errors[] = "3 ตัวหน้าต้องเป็นตัวเลข 3 หลัก";
    }
    
    if (strlen($last_three) != 3 || !is_numeric($last_three)) {
        $errors[] = "3 ตัวท้ายต้องเป็นตัวเลข 3 หลัก";
    }
    
    if (empty($errors)) {
        // บันทึกผลรางวัล
        $stmt = $conn->prepare("UPDATE lotteries SET 
                               first_prize = ?, 
                               last_two = ?, 
                               first_three = ?, 
                               last_three = ?,
                               result_date = NOW(),
                               status = 'completed'
                               WHERE id = ?");
        $stmt->bind_param("ssssi", $first_prize, $last_two, $first_three, $last_three, $lottery_id);
        
        if ($stmt->execute()) {
            // จัดการการจ่ายรางวัล
            $result_numbers = [
                'first_prize' => $first_prize,
                'last_two' => $last_two,
                'first_three' => $first_three,
                'last_three' => $last_three
            ];
            
            $process_result = processWinnings($lottery_id, $draw_date, $result_numbers);
            
            $_SESSION['message'] = "ออกผลรางวัลเรียบร้อยแล้ว! มีผู้ถูกรางวัล {$process_result['winners_count']} คน รวมเป็นเงิน " . number_format($process_result['total_payout'], 2) . " บาท";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการบันทึกผลรางวัล: " . $conn->error;
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "กรุณาแก้ไขข้อผิดพลาด: " . implode(", ", $errors);
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: lottery_results.php");
    exit;
}

// ดึงข้อมูลงวดที่ยังไม่ได้ออกผลรางวัล
$active_lotteries = [];
$result = $conn->query("SELECT * FROM lotteries WHERE status = 'active' ORDER BY draw_date DESC");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_lotteries[] = $row;
    }
}

// ดึงข้อมูลงวดที่ออกผลรางวัลแล้ว
$completed_lotteries = [];
$result = $conn->query("SELECT * FROM lotteries WHERE status = 'completed' ORDER BY result_date DESC LIMIT 10");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $completed_lotteries[] = $row;
    }
}

include 'includes/admin_header.php';
?>

<div id="layoutSidenav">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                <h1 class="mt-4">จัดการผลรางวัล</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                    <li class="breadcrumb-item active">จัดการผลรางวัล</li>
                </ol>
                
                <!-- ออกผลรางวัล -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-trophy me-1"></i>
                        ออกผลรางวัล
                    </div>
                    <div class="card-body">
                        <?php if (count($active_lotteries) > 0): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="lottery_id" class="form-label">เลือกงวด</label>
                                    <select class="form-select" id="lottery_id" name="lottery_id" required>
                                        <?php foreach ($active_lotteries as $lottery): ?>
                                            <option value="<?php echo $lottery['id']; ?>" data-date="<?php echo $lottery['draw_date']; ?>">
                                                งวดวันที่ <?php echo date('d/m/Y', strtotime($lottery['draw_date'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="draw_date" id="draw_date" value="<?php echo $active_lotteries[0]['draw_date']; ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="first_prize" class="form-label">รางวัลที่ 1</label>
                                        <input type="text" class="form-control" id="first_prize" name="first_prize" 
                                              pattern="[0-9]{6}" maxlength="6" required>
                                        <div class="form-text">ตัวเลข 6 หลัก</div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="last_two" class="form-label">2 ตัวท้าย</label>
                                        <input type="text" class="form-control" id="last_two" name="last_two" 
                                              pattern="[0-9]{2}" maxlength="2" required>
                                        <div class="form-text">ตัวเลข 2 หลัก</div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="first_three" class="form-label">3 ตัวหน้า</label>
                                        <input type="text" class="form-control" id="first_three" name="first_three" 
                                              pattern="[0-9]{3}" maxlength="3" required>
                                        <div class="form-text">ตัวเลข 3 หลัก</div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="last_three" class="form-label">3 ตัวท้าย</label>
                                        <input type="text" class="form-control" id="last_three" name="last_three" 
                                              pattern="[0-9]{3}" maxlength="3" required>
                                        <div class="form-text">ตัวเลข 3 หลัก</div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>คำเตือน:</strong> การออกผลรางวัลจะทำการคำนวณและจ่ายเงินรางวัลให้ผู้ที่ถูกรางวัลโดยอัตโนมัติ 
                                    กรุณาตรวจสอบความถูกต้องก่อนดำเนินการ
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="submit_result" class="btn btn-primary">
                                        <i class="fas fa-check-circle me-2"></i>ออกผลรางวัล
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                ไม่มีงวดที่รอการออกผลรางวัล โปรดสร้างงวดใหม่ก่อน
                            </div>
                            <a href="create_lottery.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>สร้างงวดใหม่
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- ประวัติการออกผลรางวัล -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-history me-1"></i>
                        ประวัติการออกผลรางวัล
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>งวดวันที่</th>
                                    <th>รางวัลที่ 1</th>
                                    <th>2 ตัวท้าย</th>
                                    <th>3 ตัวหน้า</th>
                                    <th>3 ตัวท้าย</th>
                                    <th>วันที่ออกรางวัล</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($completed_lotteries) > 0): ?>
                                    <?php foreach ($completed_lotteries as $lottery): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($lottery['draw_date'])); ?></td>
                                            <td class="text-primary fw-bold"><?php echo $lottery['first_prize']; ?></td>
                                            <td class="text-success fw-bold"><?php echo $lottery['last_two']; ?></td>
                                            <td class="text-info fw-bold"><?php echo $lottery['first_three']; ?></td>
                                            <td class="text-warning fw-bold"><?php echo $lottery['last_three']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($lottery['result_date'])); ?></td>
                                            <td>
                                                <a href="view_winners.php?lottery_id=<?php echo $lottery['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-users me-1"></i>ดูผู้ถูกรางวัล
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">ไม่มีประวัติการออกผลรางวัล</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'includes/admin_footer.php'; ?>
    </div>
</div>

<script>
// อัปเดตค่า draw_date เมื่อเลือกงวด
document.getElementById('lottery_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById('draw_date').value = selectedOption.getAttribute('data-date');
});

// สร้างตัวเลขสุ่มสำหรับผลรางวัล
document.addEventListener('DOMContentLoaded', function() {
    const randomButton = document.createElement('button');
    randomButton.type = 'button';
    randomButton.className = 'btn btn-secondary mb-3';
    randomButton.innerHTML = '<i class="fas fa-random me-2"></i>สุ่มตัวเลข';
    randomButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // สุ่มตัวเลข
        document.getElementById('first_prize').value = generateRandom(6);
        document.getElementById('last_two').value = generateRandom(2);
        document.getElementById('first_three').value = generateRandom(3);
        document.getElementById('last_three').value = generateRandom(3);
    });
    
    // แทรกปุ่มก่อนปุ่มส่งฟอร์ม
    const submitButton = document.querySelector('button[name="submit_result"]');
    if (submitButton) {
        submitButton.parentNode.insertBefore(randomButton, submitButton);
    }
    
    // ฟังก์ชันสร้างตัวเลขสุ่ม
    function generateRandom(length) {
        let result = '';
        for (let i = 0; i < length; i++) {
            result += Math.floor(Math.random() * 10);
        }
        return result;
    }
});
</script>