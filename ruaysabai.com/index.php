<?php
// กำหนดค่า session
session_start();

// ตรวจสอบว่ามีการล็อกอินหรือไม่
$isLoggedIn = isset($_SESSION['user_id']) ? true : false;
$userBalance = $isLoggedIn ? $_SESSION['balance'] ?? 0 : 0;

// เตรียมข้อมูลสำหรับการแสดงผลรางวัล
$lotteryResults = [
    'date' => '16 พฤษภาคม 2025',
    'firstPrize' => '123456',
    'frontThree' => '789',
    'backThree' => '456',
    'backTwo' => '56'
];

// เตรียมข้อมูลอัตราการจ่ายเงิน
$rates = [
    'firstPrize' => 900,
    'frontThree' => 500,
    'backThree' => 500,
    'backTwo' => 90
];

// ตรวจสอบการส่งค่าจากฟอร์มเติมเงิน
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deposit-submit'])) {
    $amount = $_POST['deposit-amount'] ?? 0;
    $method = $_POST['payment-method'] ?? '';
    
    // ในตัวอย่างนี้เราจะเพียงแค่แสดงข้อความแจ้งเตือน ในการใช้งานจริงควรบันทึกลงฐานข้อมูล
    $message = "ทำรายการเติมเงินจำนวน {$amount} บาท ผ่าน{$method} สำเร็จ! กรุณารอการตรวจสอบจากระบบ";
    
    // ตั้งค่าข้อความแจ้งเตือนใน session
    $_SESSION['deposit_message'] = $message;
    
    // Redirect เพื่อป้องกันการส่งฟอร์มซ้ำ
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ดึงข้อความแจ้งเตือนจาก session (ถ้ามี)
$depositMessage = '';
if (isset($_SESSION['deposit_message'])) {
    $depositMessage = $_SESSION['deposit_message'];
    unset($_SESSION['deposit_message']); // ลบข้อความหลังจากแสดงแล้ว
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบหวยออนไลน์</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4527a0;
            --secondary-color: #7953d2;
            --accent-color: #ff9800;
            --text-color: #333;
            --light-bg: #f5f5f5;
            --white: #ffffff;
            --success: #4caf50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Sarabun', sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 1.5rem;
        }
        
        nav ul li a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--accent-color);
        }
        
        .user-balance {
            background-color: var(--secondary-color);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            margin-left: 1rem;
        }
        
        .hero {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 3rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .section {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-color);
        }
        
        .lottery-result {
            text-align: center;
        }
        
        .lottery-date {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .result-card {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        
        .result-card h3 {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .result-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .price-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .price-item {
            background-color: var(--white);
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .price-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .price-item h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .price-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-accent {
            background-color: var(--accent-color);
        }
        
        .btn-accent:hover {
            background-color: #e68a00;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        
        footer {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 1rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: var(--accent-color);
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section ul li a {
            color: var(--white);
            text-decoration: none;
        }
        
        .footer-section ul li a:hover {
            color: var(--accent-color);
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            color: var(--white);
            font-size: 1.5rem;
        }
        
        .copyright {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            
            nav ul {
                margin-top: 1rem;
            }
            
            nav ul li {
                margin-left: 1rem;
                margin-right: 1rem;
            }
            
            .user-balance {
                margin-top: 1rem;
                margin-left: 0;
            }
            
            .footer-content {
                flex-direction: column;
            }
        }
    </style>
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

    <section class="hero">
        <div class="container">
            <h1>ยินดีต้อนรับสู่หวยออนไลน์</h1>
            <p>เว็บไซต์หวยออนไลน์ที่มั่นคง ปลอดภัย จ่ายจริง</p>
        </div>
    </section>

    <div class="container">
        <?php if (!empty($depositMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $depositMessage; ?>
        </div>
        <?php endif; ?>
        
        <section class="section lottery-result">
            <h2 class="section-title">ผลการออกรางวัลล่าสุด</h2>
            <div class="lottery-date">
                งวดประจำวันที่ <span id="lottery-date"><?php echo $lotteryResults['date']; ?></span>
            </div>
            
            <div class="result-grid">
                <div class="result-card">
                    <h3>รางวัลที่ 1</h3>
                    <div class="result-number" id="first-prize"><?php echo $lotteryResults['firstPrize']; ?></div>
                </div>
                
                <div class="result-card">
                    <h3>เลขหน้า 3 ตัว</h3>
                    <div class="result-number" id="front-three"><?php echo $lotteryResults['frontThree']; ?></div>
                </div>
                
                <div class="result-card">
                    <h3>เลขท้าย 3 ตัว</h3>
                    <div class="result-number" id="back-three"><?php echo $lotteryResults['backThree']; ?></div>
                </div>
                
                <div class="result-card">
                    <h3>เลขท้าย 2 ตัว</h3>
                    <div class="result-number" id="back-two"><?php echo $lotteryResults['backTwo']; ?></div>
                </div>
            </div>
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

        <section class="section">
            <h2 class="section-title">เติมเงินเข้าระบบ</h2>
            <form id="deposit-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="deposit-amount">จำนวนเงิน (บาท)</label>
                    <input type="number" id="deposit-amount" name="deposit-amount" class="form-control" min="100" placeholder="ระบุจำนวนเงิน" required>
                </div>
                
                <div class="form-group">
                    <label for="payment-method">วิธีการชำระเงิน</label>
                    <select id="payment-method" name="payment-method" class="form-control" required>
                        <option value="" disabled selected>เลือกวิธีการชำระเงิน</option>
                        <option value="โอนผ่านธนาคาร">โอนเงินผ่านธนาคาร</option>
                        <option value="พร้อมเพย์">พร้อมเพย์</option>
                        <option value="ทรูมันนี่วอลเล็ท">ทรูมันนี่วอลเล็ท</option>
                    </select>
                </div>
                
                <button type="submit" name="deposit-submit" class="btn btn-accent">เติมเงิน</button>
            </form>
        </section>
    </div>

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