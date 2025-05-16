<?php
// นำเข้าไฟล์โครงสร้างพื้นฐาน
require_once 'structure.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'buy.php';
    $_SESSION['login_message'] = 'กรุณาเข้าสู่ระบบก่อนซื้อหวย';
    header('Location: login.php');
    exit;
}

// ข้อมูลงวดปัจจุบัน
$currentLottery = [
    'id' => 2,
    'date' => '16 พฤษภาคม 2025',
    'status' => 'pending'
];

// อัตราการจ่ายเงิน
$rates = [
    'firstPrize' => 900,
    'frontThree' => 500,
    'backThree' => 500,
    'backTwo' => 90
];

// คำอธิบายประเภทหวย
$lotteryTypes = [
    'firstPrize' => 'รางวัลที่ 1',
    'frontThree' => 'เลขหน้า 3 ตัว',
    'backThree' => 'เลขท้าย 3 ตัว',
    'backTwo' => 'เลขท้าย 2 ตัว'
];

// ตรวจสอบการส่งค่าจากฟอร์มซื้อหวย
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buy-submit'])) {
    $lotteryType = $_POST['lottery-type'] ?? '';
    $number = $_POST['lottery-number'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    
    // ตรวจสอบว่าเลขถูกต้องตามประเภทหรือไม่
    $isValidNumber = false;
    $requiredLength = 0;
    
    if ($lotteryType === 'firstPrize') {
        $requiredLength = 6;
        $isValidNumber = preg_match('/^\d{6}$/', $number);
    } else if ($lotteryType === 'frontThree' || $lotteryType === 'backThree') {
        $requiredLength = 3;
        $isValidNumber = preg_match('/^\d{3}$/', $number);
    } else if ($lotteryType === 'backTwo') {
        $requiredLength = 2;
        $isValidNumber = preg_match('/^\d{2}$/', $number);
    }
    
    if (!$isValidNumber) {
        $error = "กรุณาระบุเลข {$requiredLength} หลักที่ถูกต้อง";
    } else if ($amount < 10) {
        $error = "จำนวนเงินต้องไม่น้อยกว่า 10 บาท";
    } else if ($amount > $userBalance) {
        $error = "ยอดเงินคงเหลือไม่เพียงพอ";
    } else {
        // คำนวณเงินรางวัลที่อาจจะได้
        $potentialWin = $amount * $rates[$lotteryType];
        
        // บันทึกข้อมูลการซื้อหวย (ในกรณีจริงควรบันทึกลงฐานข้อมูล)
        $success = true;
        
        if ($success) {
            // หักเงินจากยอดคงเหลือ
            $_SESSION['balance'] -= $amount;
            
            // ตั้งค่าข้อความแจ้งเตือน
            $message = "ซื้อ {$lotteryTypes[$lotteryType]} เลข {$number} จำนวน {$amount} บาท สำเร็จ!";
            $_SESSION['buy_message'] = $message;
            
            // Redirect เพื่อป้องกันการส่งฟอร์มซ้ำ
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "เกิดข้อผิดพลาดในการซื้อหวย กรุณาลองใหม่อีกครั้ง";
        }
    }
}

// ดึงข้อความแจ้งเตือนจาก session (ถ้ามี)
$buyMessage = '';
if (isset($_SESSION['buy_message'])) {
    $buyMessage = $_SESSION['buy_message'];
    unset($_SESSION['buy_message']); // ลบข้อความหลังจากแสดงแล้ว
}

// แสดงส่วน header
renderHeader('ซื้อหวย - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);
?>

<section class="page-title">
    <div class="container">
        <h1>ซื้อหวย</h1>
        <p>เลือกซื้อหวยได้หลากหลายประเภท</p>
    </div>
</section>

<div class="container">
    <?php if (!empty($buyMessage)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $buyMessage; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <section class="section">
        <h2 class="section-title">งวดปัจจุบัน</h2>
        <p>งวดประจำวันที่: <strong><?php echo $currentLottery['date']; ?></strong></p>
        <p>สถานะ: <span class="badge status-pending">กำลังเปิดรับซื้อ</span></p>
    </section>
    
    <section class="section">
        <h2 class="section-title">ซื้อหวย</h2>
        
        <form id="buy-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="lottery-type">เลือกประเภท</label>
                <select id="lottery-type" name="lottery-type" class="form-control" required>
                    <option value="firstPrize">รางวัลที่ 1 (เลข 6 หลัก) - บาทละ <?php echo $rates['firstPrize']; ?> บาท</option>
                    <option value="frontThree">เลขหน้า 3 ตัว - บาทละ <?php echo $rates['frontThree']; ?> บาท</option>
                    <option value="backThree">เลขท้าย 3 ตัว - บาทละ <?php echo $rates['backThree']; ?> บาท</option>
                    <option value="backTwo">เลขท้าย 2 ตัว - บาทละ <?php echo $rates['backTwo']; ?> บาท</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="lottery-number">เลขที่ต้องการซื้อ</label>
                <input type="text" id="lottery-number" name="lottery-number" class="form-control" placeholder="ระบุเลขที่ต้องการซื้อ" required>
                <div id="number-help" class="form-text">ระบุเลขตามประเภทที่เลือก (2-6 หลัก)</div>
            </div>
            
            <div class="form-group">
                <label for="amount">จำนวนเงิน (บาท)</label>
                <input type="number" id="amount" name="amount" class="form-control" min="10" placeholder="ระบุจำนวนเงิน" required>
            </div>
            
            <div class="form-group">
                <div id="potential-win">
                    เงินรางวัลที่อาจจะได้: <span>0</span> บาท
                </div>
            </div>
            
            <button type="submit" name="buy-submit" class="btn btn-accent">ซื้อหวย</button>
        </form>
    </section>
    
    <section class="section">
        <h2 class="section-title">อัตราการจ่ายเงิน</h2>
        <div class="price-list">
            <div class="price-item">
                <h3>รางวัลที่ 1</h3>
                <div class="price-value">บาทละ <span id="rate-first-prize"><?php echo $rates['firstPrize']; ?></span> บาท</div>
            </div>
            
            <div class="price-item">
                <h3>เลขหน้า 3 ตัว</h3>
                <div class="price-value">บาทละ <span id="rate-front-three"><?php echo $rates['frontThree']; ?></span> บาท</div>
            </div>
            
            <div class="price-item">
                <h3>เลขท้าย 3 ตัว</h3>
                <div class="price-value">บาทละ <span id="rate-back-three"><?php echo $rates['backThree']; ?></span> บาท</div>
            </div>
            
            <div class="price-item">
                <h3>เลขท้าย 2 ตัว</h3>
                <div class="price-value">บาทละ <span id="rate-back-two"><?php echo $rates['backTwo']; ?></span> บาท</div>
            </div>
        </div>
    </section>
</div>

<style>
.form-text {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

#potential-win {
    background-color: #f9f9f9;
    padding: 1rem;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 1.1rem;
}

#potential-win span {
    font-weight: bold;
    color: var(--accent-color);
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    display: inline-block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lotteryTypeSelect = document.getElementById('lottery-type');
    const lotteryNumberInput = document.getElementById('lottery-number');
    const amountInput = document.getElementById('amount');
    const potentialWinSpan = document.querySelector('#potential-win span');
    
    // ฟังก์ชันสำหรับตรวจสอบความถูกต้องของเลข
    function validateNumber() {
        const lotteryType = lotteryTypeSelect.value;
        const number = lotteryNumberInput.value;
        
        let requiredLength = 6;
        if (lotteryType === 'firstPrize') {
            requiredLength = 6;
        } else if (lotteryType === 'frontThree' || lotteryType === 'backThree') {
            requiredLength = 3;
        } else if (lotteryType === 'backTwo') {
            requiredLength = 2;
        }
        
        // ตั้งค่าข้อความช่วยเหลือ
        document.getElementById('number-help').textContent = `ระบุเลข ${requiredLength} หลัก`;
        
        // ตรวจสอบความถูกต้อง
        if (number === '') {
            lotteryNumberInput.setCustomValidity('');
            return;
        }
        
        if (!/^\d+$/.test(number)) {
            lotteryNumberInput.setCustomValidity('กรุณาระบุตัวเลขเท่านั้น');
        } else if (number.length !== requiredLength) {
            lotteryNumberInput.setCustomValidity(`กรุณาระบุเลข ${requiredLength} หลัก`);
        } else {
            lotteryNumberInput.setCustomValidity('');
        }
    }
    
    // ฟังก์ชันสำหรับคำนวณเงินรางวัลที่อาจจะได้
    function calculatePotentialWin() {
        const lotteryType = lotteryTypeSelect.value;
        const amount = amountInput.value;
        
        let rate = 0;
        if (lotteryType === 'firstPrize') {
            rate = <?php echo $rates['firstPrize']; ?>;
        } else if (lotteryType === 'frontThree') {
            rate = <?php echo $rates['frontThree']; ?>;
        } else if (lotteryType === 'backThree') {
            rate = <?php echo $rates['backThree']; ?>;
        } else if (lotteryType === 'backTwo') {
            rate = <?php echo $rates['backTwo']; ?>;
        }
        
        const potentialWin = amount * rate;
        potentialWinSpan.textContent = potentialWin.toLocaleString();
    }
    
    // เพิ่ม event listeners
    lotteryTypeSelect.addEventListener('change', function() {
        validateNumber();
        calculatePotentialWin();
    });
    
    lotteryNumberInput.addEventListener('input', validateNumber);
    
    amountInput.addEventListener('input', calculatePotentialWin);
    
    // ตรวจสอบเริ่มต้น
    validateNumber();
});
</script>

<?php
// แสดงส่วน footer
renderFooter();
?>