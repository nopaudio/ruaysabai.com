<?php
// ไฟล์โครงสร้างพื้นฐานสำหรับทุกหน้า
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
$isLoggedIn = isset($_SESSION['user_id']) ? true : false;
$userBalance = $isLoggedIn ? $_SESSION['balance'] ?? 0 : 0;

// ฟังก์ชันพื้นฐานที่ใช้ร่วมกัน
function getStatusText($status) {
    switch ($status) {
        case 'success':
            return 'สำเร็จ';
        case 'pending':
            return 'รอตรวจสอบ';
        case 'failed':
            return 'ไม่สำเร็จ';
        default:
            return $status;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'success':
            return 'status-success';
        case 'pending':
            return 'status-pending';
        case 'failed':
            return 'status-failed';
        default:
            return '';
    }
}

// ฟังก์ชันสำหรับแสดงส่วน header
function renderHeader($title = 'ระบบหวยออนไลน์', $isLoggedIn = false, $userBalance = 0) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="styles.css" rel="stylesheet">
    </head>
    <body>
        <header>
            <div class="container header-content">
                <div class="logo">หวยออนไลน์</div>
                <nav>
                    <ul>
                        <li><a href="index.php">หน้าหลัก</a></li>
                        <li><a href="wallet.php">เติมเงิน</a></li>
                        <li><a href="history.php">ประวัติ</a></li>
                        <li><a href="contact.php">ติดต่อเรา</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="profile.php">โปรไฟล์</a></li>
                            <li><a href="logout.php">ออกจากระบบ</a></li>
                        <?php else: ?>
                            <li><a href="login.php">เข้าสู่ระบบ</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="user-balance">
                    ยอดเงินคงเหลือ: <span id="user-balance"><?php echo number_format($userBalance, 2); ?></span> บาท
                </div>
            </div>
        </header>
    <?php
}

// ฟังก์ชันสำหรับแสดงส่วน footer
function renderFooter() {
    ?>
        <footer>
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h3>เกี่ยวกับเรา</h3>
                        <p>เว็บไซต์หวยออนไลน์ที่มั่นคง ปลอดภัย จ่ายจริง 100%</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>ลิงก์ด่วน</h3>
                        <ul>
                            <li><a href="index.php">หน้าหลัก</a></li>
                            <li><a href="wallet.php">เติมเงิน</a></li>
                            <li><a href="history.php">ประวัติ</a></li>
                            <li><a href="contact.php">ติดต่อเรา</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>ติดต่อเรา</h3>
                        <p>อีเมล: support@lotterythai.com</p>
                        <p>โทร: 02-123-4567</p>
                        <p>Line ID: @lotterythai</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>ติดตามเรา</h3>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-line"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> ระบบหวยออนไลน์. สงวนสิทธิ์ทุกประการ.
                </div>
            </div>
        </footer>
    </body>
    </html>
    <?php
}
?>