<?php
// นำเข้าไฟล์โครงสร้างพื้นฐาน
require_once 'structure.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'wallet.php';
    $_SESSION['login_message'] = 'กรุณาเข้าสู่ระบบก่อนเติมเงิน';
    header('Location: login.php');
    exit;
}

// ข้อมูลประวัติการเติมเงิน (สำหรับตัวอย่าง)
$depositHistory = [
    [
        'id' => 1,
        'date' => '2025-05-13',
        'time' => '09:15:42',
        'amount' => 1000,
        'method' => 'โอนผ่านธนาคาร',
        'reference' => 'TX12345678',
        'status' => 'success'
    ],
    [
        'id' => 2,
        'date' => '2025-05-10',
        'time' => '14:30:22',
        'amount' => 500,
        'method' => 'พร้อมเพย์',
        'reference' => 'PP98765432',
        'status' => 'success'
    ],
    [
        'id' => 3,
        'date' => '2025-05-14',
        'time' => '10:05:18',
        'amount' => 200,
        'method' => 'ทรูมันนี่วอลเล็ท',
        'reference' => 'TM56781234',
        'status' => 'pending'
    ]
];

// ตรวจสอบการส่งค่าจากฟอร์มเติมเงิน
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['bank-submit'])) {
        $amount = $_POST['bank-amount'] ?? 0;
        $date = $_POST['bank-transfer-date'] ?? '';
        $time = $_POST['bank-transfer-time'] ?? '';
        $bank = $_POST['bank-from'] ?? '';
        $reference = $_POST['bank-reference'] ?? '';
        
        // สร้างข้อความแจ้งเตือน
        $message = "ทำรายการเติมเงินจำนวน {$amount} บาท ผ่านธนาคาร สำเร็จ! กรุณารอการตรวจสอบจากระบบ";
        
        // เพิ่มข้อมูลลงในประวัติ (ในการใช้งานจริงควรบันทึกลงฐานข้อมูล)
        array_unshift($depositHistory, [
            'id' => time(),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'amount' => $amount,
            'method' => 'โอนผ่านธนาคาร',
            'reference' => $reference,
            'status' => 'pending'
        ]);
        
        // ตั้งค่าข้อความแจ้งเตือนใน session
        $_SESSION['deposit_message'] = $message;
        
        // Redirect เพื่อป้องกันการส่งฟอร์มซ้ำ
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    else if (isset($_POST['promptpay-submit'])) {
        $amount = $_POST['promptpay-amount'] ?? 0;
        $date = $_POST['promptpay-transfer-date'] ?? '';
        $time = $_POST['promptpay-transfer-time'] ?? '';
        $reference = $_POST['promptpay-reference'] ?? '';
        
        // สร้างข้อความแจ้งเตือน
        $message = "ทำรายการเติมเงินจำนวน {$amount} บาท ผ่านพร้อมเพย์ สำเร็จ! กรุณารอการตรวจสอบจากระบบ";
        
        // เพิ่มข้อมูลลงในประวัติ
        array_unshift($depositHistory, [
            'id' => time(),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'amount' => $amount,
            'method' => 'พร้อมเพย์',
            'reference' => $reference,
            'status' => 'pending'
        ]);
        
        $_SESSION['deposit_message'] = $message;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    else if (isset($_POST['truemoney-submit'])) {
        $amount = $_POST['truemoney-amount'] ?? 0;
        $phone = $_POST['truemoney-phone'] ?? '';
        $date = $_POST['truemoney-transfer-date'] ?? '';
        $time = $_POST['truemoney-transfer-time'] ?? '';
        $reference = $_POST['truemoney-reference'] ?? '';
        
        // สร้างข้อความแจ้งเตือน
        $message = "ทำรายการเติมเงินจำนวน {$amount} บาท ผ่านทรูมันนี่วอลเล็ท สำเร็จ! กรุณารอการตรวจสอบจากระบบ";
        
        // เพิ่มข้อมูลลงในประวัติ
        array_unshift($depositHistory, [
            'id' => time(),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'amount' => $amount,
            'method' => 'ทรูมันนี่วอลเล็ท',
            'reference' => $reference,
            'status' => 'pending'
        ]);
        
        $_SESSION['deposit_message'] = $message;
        header('Location: ' . $_SERVER['PHP_SELF']);

        exit;
    }
}

// ดึงข้อความแจ้งเตือนจาก session (ถ้ามี)
$depositMessage = '';
if (isset($_SESSION['deposit_message'])) {
    $depositMessage = $_SESSION['deposit_message'];
    unset($_SESSION['deposit_message']); // ลบข้อความหลังจากแสดงแล้ว
}

// แสดงส่วน header
renderHeader('เติมเงิน - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);
?>

<section class="page-title">
    <div class="container">
        <h1>เติมเงินเข้าระบบ</h1>
        <p>เติมเงินง่ายๆ ได้หลายช่องทาง รวดเร็วปลอดภัย</p>
    </div>
</section>

<div class="container">
    <?php if (!empty($depositMessage)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $depositMessage; ?>
    </div>
    <?php endif; ?>
    
    <section class="section">
        <h2 class="section-title">เลือกวิธีการเติมเงิน</h2>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> เงินจะเข้าระบบภายใน 5 นาที หลังจากทำรายการโอนเงินเรียบร้อยแล้ว
        </div>
        
        <div class="payment-methods">
            <div class="payment-method" data-method="bank">
                <i class="fas fa-university"></i>
                <h3>โอนผ่านธนาคาร</h3>
                <p>โอนเงินผ่านบัญชีธนาคาร</p>
            </div>
            
            <div class="payment-method" data-method="promptpay">
                <i class="fas fa-qrcode"></i>
                <h3>พร้อมเพย์</h3>
                <p>โอนเงินผ่านพร้อมเพย์</p>
            </div>
            
            <div class="payment-method" data-method="truemoney">
                <i class="fas fa-wallet"></i>
                <h3>ทรูมันนี่วอลเล็ท</h3>
                <p>โอนเงินผ่านทรูมันนี่</p>
            </div>
        </div>
        
        <div id="bank-info" class="payment-info">
            <h3>โอนเงินผ่านธนาคาร</h3>
            <p>กรุณาโอนเงินเข้าบัญชีดังต่อไปนี้</p>
            
            <div class="bank-account">
                <img src="images/kbank.png" alt="ธนาคารกสิกรไทย" class="bank-logo">
                <div>
                    <div>ธนาคารกสิกรไทย</div>
                    <div>เลขบัญชี: 123-4-56789-0 <button class="copy-button" data-copy="123-4-56789-0">คัดลอก</button></div>
                    <div>ชื่อบัญชี: บริษัท หวยออนไลน์ จำกัด</div>
                </div>
            </div>
            
            <div class="bank-account">
                <img src="images/scb.png" alt="ธนาคารไทยพาณิชย์" class="bank-logo">
                <div>
                    <div>ธนาคารไทยพาณิชย์</div>
                    <div>เลขบัญชี: 987-6-54321-0 <button class="copy-button" data-copy="987-6-54321-0">คัดลอก</button></div>
                    <div>ชื่อบัญชี: บริษัท หวยออนไลน์ จำกัด</div>
                </div>
            </div>
            
            <form id="bank-deposit-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="bank-amount">จำนวนเงิน (บาท)</label>
                    <input type="number" id="bank-amount" name="bank-amount" class="form-control" min="100" placeholder="ระบุจำนวนเงิน" required>
                </div>
                
                <div class="form-group">
                    <label for="bank-transfer-date">วันที่โอนเงิน</label>
                    <input type="date" id="bank-transfer-date" name="bank-transfer-date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="bank-transfer-time">เวลาที่โอนเงิน</label>
                    <input type="time" id="bank-transfer-time" name="bank-transfer-time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="bank-from">ธนาคารที่โอนมา</label>
                    <select id="bank-from" name="bank-from" class="form-control" required>
                        <option value="" disabled selected>เลือกธนาคาร</option>
                        <option value="kbank">ธนาคารกสิกรไทย</option>
                        <option value="scb">ธนาคารไทยพาณิชย์</option>
                        <option value="bbl">ธนาคารกรุงเทพ</option>
                        <option value="ktb">ธนาคารกรุงไทย</option>
                        <option value="bay">ธนาคารกรุงศรีอยุธยา</option>
                        <option value="tmb">ธนาคารทหารไทยธนชาต</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bank-reference">เลขอ้างอิงการโอนเงิน</label>
                    <input type="text" id="bank-reference" name="bank-reference" class="form-control" placeholder="เลขอ้างอิงการโอนเงิน" required>
                </div>
                
                <button type="submit" name="bank-submit" class="btn btn-accent btn-block">ยืนยันการเติมเงิน</button>
            </form>
        </div>
        
        <div id="promptpay-info" class="payment-info">
            <h3>โอนเงินผ่านพร้อมเพย์</h3>
            <p>สแกน QR Code ด้านล่างนี้ด้วยแอปธนาคารของท่านเพื่อทำการโอนเงิน</p>
            
            <img src="images/promptpay-qr.png" alt="พร้อมเพย์ QR Code" class="qr-code">
            
            <p>หรือโอนเงินไปยังเลขพร้อมเพย์: 1234567890123 <button class="copy-button" data-copy="1234567890123">คัดลอก</button></p>
            
            <form id="promptpay-deposit-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="promptpay-amount">จำนวนเงิน (บาท)</label>
                    <input type="number" id="promptpay-amount" name="promptpay-amount" class="form-control" min="100" placeholder="ระบุจำนวนเงิน" required>
                </div>
                
                <div class="form-group">
                    <label for="promptpay-transfer-date">วันที่โอนเงิน</label>
                    <input type="date" id="promptpay-transfer-date" name="promptpay-transfer-date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="promptpay-transfer-time">เวลาที่โอนเงิน</label>
                    <input type="time" id="promptpay-transfer-time" name="promptpay-transfer-time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="promptpay-reference">เลขอ้างอิงการโอนเงิน</label>
                    <input type="text" id="promptpay-reference" name="promptpay-reference" class="form-control" placeholder="เลขอ้างอิงการโอนเงิน" required>
                </div>
                
                <button type="submit" name="promptpay-submit" class="btn btn-accent btn-block">ยืนยันการเติมเงิน</button>
            </form>
        </div>
        
        <div id="truemoney-info" class="payment-info">
            <h3>โอนเงินผ่านทรูมันนี่วอลเล็ท</h3>
            <p>โอนเงินไปยังหมายเลข: 089-123-4567 <button class="copy-button" data-copy="089-123-4567">คัดลอก</button></p>
            
            <form id="truemoney-deposit-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="truemoney-amount">จำนวนเงิน (บาท)</label>
                    <input type="number" id="truemoney-amount" name="truemoney-amount" class="form-control" min="100" placeholder="ระบุจำนวนเงิน" required>
                </div>
                
                <div class="form-group">
                    <label for="truemoney-phone">หมายเลขโทรศัพท์ของท่าน</label>
                    <input type="tel" id="truemoney-phone" name="truemoney-phone" class="form-control" placeholder="เบอร์โทรศัพท์ที่ใช้โอนเงิน" required>
                </div>
                
                <div class="form-group">
                    <label for="truemoney-transfer-date">วันที่โอนเงิน</label>
                    <input type="date" id="truemoney-transfer-date" name="truemoney-transfer-date" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="truemoney-transfer-time">เวลาที่โอนเงิน</label>
                    <input type="time" id="truemoney-transfer-time" name="truemoney-transfer-time" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="truemoney-reference">เลขอ้างอิงการโอนเงิน</label>
                    <input type="text" id="truemoney-reference" name="truemoney-reference" class="form-control" placeholder="เลขอ้างอิงการโอนเงิน" required>
                </div>
                
                <button type="submit" name="truemoney-submit" class="btn btn-accent btn-block">ยืนยันการเติมเงิน</button>
            </form>
        </div>
    </section>
<section class="section">
        <h2 class="section-title">ประวัติการเติมเงินล่าสุด</h2>
        
        <?php if (empty($depositHistory)): ?>
            <p>ยังไม่มีประวัติการเติมเงิน</p>
        <?php else: ?>
            <?php foreach ($depositHistory as $deposit): ?>
                <div class="history-item">
                    <div>
                        <div class="history-date">
                            <?php echo $deposit['date']; ?> <?php echo $deposit['time']; ?>
                        </div>
                        <div>
                            <?php echo $deposit['method']; ?> (Ref: <?php echo $deposit['reference']; ?>)
                        </div>
                    </div>
                    <div class="history-amount">
                        <?php echo number_format($deposit['amount'], 2); ?> บาท
                    </div>
                    <div class="history-status <?php echo getStatusClass($deposit['status']); ?>">
                        <?php echo getStatusText($deposit['status']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<?php
// แสดงส่วน footer
renderFooter();
?>

<script>
// จัดการการแสดงข้อมูลวิธีการชำระเงิน
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // ลบ class active จากทุก method
            paymentMethods.forEach(m => m.classList.remove('active'));
            
            // เพิ่ม class active ให้กับ method ที่ถูกคลิก
            this.classList.add('active');
            
            // ซ่อนทุก payment info
            document.querySelectorAll('.payment-info').forEach(info => {
                info.classList.remove('active');
            });
            
            // แสดง payment info ที่เกี่ยวข้อง
            const methodId = this.getAttribute('data-method');
            document.getElementById(`${methodId}-info`).classList.add('active');
        });
    });
    
    // เลือกวิธีการชำระเงินแรกเป็นค่าเริ่มต้น
    paymentMethods[0].click();
    
    // จัดการปุ่มคัดลอก
    document.querySelectorAll('.copy-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const textToCopy = this.getAttribute('data-copy');
            
            // คัดลอกข้อความไปยัง clipboard
            navigator.clipboard.writeText(textToCopy).then(() => {
                // เปลี่ยนข้อความปุ่มชั่วคราว
                const originalText = this.textContent;
                this.textContent = 'คัดลอกแล้ว';
                
                // เปลี่ยนกลับหลังจาก 2 วินาที
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
    });
});
</script>