<?php
require_once 'config.php';

// ตรวจสอบการล็อกอิน
if (!isUserLoggedIn()) {
    header('Location: login.php?redirect=subscribe.php');
    exit;
}

$user = getCurrentUser();
$plan = $_GET['plan'] ?? '';

// กำหนดแพ็กเกจ
$packages = [
    'monthly' => [
        'name' => 'แพ็กเกจรายเดือน',
        'price' => 150,
        'period' => 'เดือน',
        'credits' => 60,
        'description' => '60 ครั้งต่อเดือน'
    ],
    'yearly' => [
        'name' => 'แพ็กเกจรายปี',
        'price' => 1200,
        'period' => 'ปี',
        'credits' => 'ไม่จำกัด',
        'description' => 'ใช้งานไม่จำกัดตลอดปี'
    ]
];

if (!isset($packages[$plan])) {
    $plan = 'monthly'; // ค่าเริ่มต้น
}

$selectedPackage = $packages[$plan];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สมัครแพ็กเกจเช่าซื้อ | AI Prompt Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .subscribe-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .subscribe-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 40px;
            text-align: center;
            color: white;
        }
        .subscribe-header h1 {
            margin: 0 0 15px 0;
            font-weight: 700;
        }
        .subscribe-content {
            padding: 40px;
        }
        .package-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }
        .package-card {
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .package-card.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }
        .package-period {
            color: #666;
            margin-bottom: 15px;
        }
        .package-features {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .package-features li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .package-features li:last-child {
            border-bottom: none;
        }
        .current-package {
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid rgba(34, 197, 94, 0.3);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .payment-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .btn-subscribe {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 18px;
            width: 100%;
            transition: all 0.3s ease;
            color: white;
        }
        .btn-subscribe:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        @media (max-width: 768px) {
            .package-selection {
                grid-template-columns: 1fr;
            }
            .subscribe-container {
                margin: 20px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="subscribe-container">
        <div class="subscribe-header">
            <h1><i class="fas fa-crown"></i> สมัครแพ็กเกจเช่าซื้อ</h1>
            <p>เพิ่มสิทธิ์การใช้งาน AI Prompt Generator</p>
        </div>
        
        <div class="subscribe-content">
            <!-- แสดงแพ็กเกจปัจจุบัน -->
            <?php if ($user['member_type'] !== 'free'): ?>
                <div class="current-package">
                    <h5><i class="fas fa-info-circle"></i> แพ็กเกจปัจจุบัน</h5>
                    <p class="mb-0">
                        <strong>
                            <?= $user['member_type'] == 'monthly' ? 'แพ็กเกจรายเดือน' : 'แพ็กเกจรายปี' ?>
                        </strong>
                        <?php if ($user['expire_date']): ?>
                            <br>หมดอายุ: <?= date('d/m/Y', strtotime($user['expire_date'])) ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- เลือกแพ็กเกจ -->
            <h4 class="mb-4">เลือกแพ็กเกจที่ต้องการ</h4>
            <div class="package-selection">
                <div class="package-card <?= $plan == 'monthly' ? 'active' : '' ?>" onclick="selectPackage('monthly')">
                    <div class="package-price">฿150</div>
                    <div class="package-period">ต่อเดือน</div>
                    <h5>แพ็กเกจรายเดือน</h5>
                    <ul class="package-features">
                        <li><i class="fas fa-check text-success"></i> 60 ครั้งต่อเดือน</li>
                        <li><i class="fas fa-check text-success"></i> ใช้งานได้ทุกฟีเจอร์</li>
                        <li><i class="fas fa-check text-success"></i> ยกเลิกได้ทุกเมื่อ</li>
                    </ul>
                </div>
                
                <div class="package-card <?= $plan == 'yearly' ? 'active' : '' ?>" onclick="selectPackage('yearly')">
                    <div class="package-price">฿1,200</div>
                    <div class="package-period">ต่อปี</div>
                    <h5>แพ็กเกจรายปี</h5>
                    <ul class="package-features">
                        <li><i class="fas fa-star text-warning"></i> <strong>ใช้งานไม่จำกัด</strong></li>
                        <li><i class="fas fa-check text-success"></i> ประหยัดกว่า 33%</li>
                        <li><i class="fas fa-check text-success"></i> เหมาะสำหรับผู้ใช้งานหนัก</li>
                    </ul>
                </div>
            </div>

            <!-- ข้อมูลการชำระเงิน -->
            <div class="payment-info">
                <h5><i class="fas fa-credit-card"></i> ข้อมูลการชำระเงิน</h5>
                <p><strong>แพ็กเกจที่เลือก:</strong> <?= $selectedPackage['name'] ?></p>
                <p><strong>ราคา:</strong> ฿<?= number_format($selectedPackage['price']) ?> / <?= $selectedPackage['period'] ?></p>
                <p><strong>สิทธิ์ที่ได้:</strong> <?= $selectedPackage['description'] ?></p>
                
                <hr>
                
                <h6>วิธีการชำระเงิน:</h6>
                <p class="mb-2">📱 <strong>PromptPay:</strong> 080-444-1958</p>
                <p class="mb-2">🏦 <strong>ธนาคารกสิกรไทย:</strong> 123-4-56789-0 (นาย ทดสอบ ระบบ)</p>
                <p class="mb-0">💳 <strong>True Money Wallet:</strong> 080-444-1958</p>
            </div>

            <!-- คำแนะนำ -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> วิธีการสมัคร:</h6>
                <ol class="mb-0">
                    <li>กดปุ่ม "สมัครแพ็กเกจ" ด้านล่าง</li>
                    <li>ทำการโอนเงินตามช่องทางที่เลือก</li>
                    <li>ส่งหลักฐานการโอนมาที่ Line: @promptease</li>
                    <li>รอการยืนยันและเปิดใช้งาน (ภายใน 24 ชม.)</li>
                </ol>
            </div>

            <!-- ปุ่มสมัคร -->
            <button type="button" class="btn btn-subscribe" onclick="subscribe()">
                <i class="fas fa-crown"></i> สมัคร<?= $selectedPackage['name'] ?> - ฿<?= number_format($selectedPackage['price']) ?>
            </button>
            
            <div class="text-center mt-3">
                <a href="profile.php" class="text-muted">← กลับไปโปรไฟล์</a>
            </div>
        </div>
    </div>

    <script>
        function selectPackage(plan) {
            window.location.href = 'subscribe.php?plan=' + plan;
        }
        
        function subscribe() {
            const plan = '<?= $plan ?>';
            const packageName = '<?= $selectedPackage['name'] ?>';
            const price = <?= $selectedPackage['price'] ?>;
            
            if (confirm(`คุณต้องการสมัคร ${packageName} ในราคา ฿${price.toLocaleString()} ใช่หรือไม่?`)) {
                // เปิด Line หรือแสดงข้อมูลการติดต่อ
                alert('กรุณาทำการโอนเงินแล้วส่งหลักฐานมาที่ Line: @promptease\n\nรายละเอียดการโอน:\n• PromptPay: 080-444-1958\n• ธนาคารกสิกรไทย: 123-4-56789-0\n• True Money: 080-444-1958\n\nระบุ: ชื่อผู้ใช้ <?= $user['username'] ?> และแพ็กเกจที่สมัคร');
                
                // บันทึกคำขอสมัครลงฐานข้อมูล (ถ้าต้องการ)
                // ส่งไปหน้าขอบคุณหรือหน้าติดตามสถานะ
                // window.location.href = 'payment_pending.php?plan=' + plan;
            }
        }
    </script>
</body>
</html>