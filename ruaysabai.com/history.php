<?php
// นำเข้าไฟล์โครงสร้างพื้นฐาน
require_once 'structure.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'history.php';
    $_SESSION['login_message'] = 'กรุณาเข้าสู่ระบบก่อนดูประวัติ';
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

// ข้อมูลประวัติการซื้อหวย (สำหรับตัวอย่าง)
$purchaseHistory = [
    [
        'id' => 1,
        'date' => '2025-05-12',
        'time' => '18:22:15',
        'lottery_type' => 'รางวัลที่ 1',
        'number' => '123456',
        'amount' => 100,
        'potential_win' => 100 * 900, // จำนวนเงิน * อัตราการจ่าย
        'status' => 'pending' // pending, win, lose
    ],
    [
        'id' => 2,
        'date' => '2025-05-11',
        'time' => '12:45:30',
        'lottery_type' => 'เลขท้าย 2 ตัว',
        'number' => '56',
        'amount' => 50,
        'potential_win' => 50 * 90,
        'status' => 'win'
    ],
    [
        'id' => 3,
        'date' => '2025-05-09',
        'time' => '09:10:05',
        'lottery_type' => 'เลขหน้า 3 ตัว',
        'number' => '789',
        'amount' => 200,
        'potential_win' => 200 * 500,
        'status' => 'lose'
    ]
];

// แสดงส่วน header
renderHeader('ประวัติการใช้งาน - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);
?>

<section class="page-title">
    <div class="container">
        <h1>ประวัติการใช้งาน</h1>
        <p>ประวัติการซื้อหวยและการเติมเงินของคุณ</p>
    </div>
</section>

<div class="container">
    <section class="section">
        <h2 class="section-title">ประวัติการซื้อหวย</h2>
        
        <?php if (empty($purchaseHistory)): ?>
            <p>ยังไม่มีประวัติการซื้อหวย</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>ประเภท</th>
                            <th>หมายเลข</th>
                            <th>จำนวนเงิน</th>
                            <th>เงินรางวัล</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseHistory as $purchase): ?>
                            <tr>
                                <td><?php echo $purchase['date']; ?> <?php echo $purchase['time']; ?></td>
                                <td><?php echo $purchase['lottery_type']; ?></td>
                                <td><?php echo $purchase['number']; ?></td>
                                <td><?php echo number_format($purchase['amount'], 2); ?> บาท</td>
                                <td><?php echo number_format($purchase['potential_win'], 2); ?> บาท</td>
                                <td>
                                    <?php if ($purchase['status'] === 'pending'): ?>
                                        <span class="badge status-pending">รอผล</span>
                                    <?php elseif ($purchase['status'] === 'win'): ?>
                                        <span class="badge status-success">ถูกรางวัล</span>
                                    <?php elseif ($purchase['status'] === 'lose'): ?>
                                        <span class="badge status-failed">ไม่ถูกรางวัล</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    
    <section class="section">
        <h2 class="section-title">ประวัติการเติมเงิน</h2>
        
        <?php if (empty($depositHistory)): ?>
            <p>ยังไม่มีประวัติการเติมเงิน</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>วิธีการเติมเงิน</th>
                            <th>อ้างอิง</th>
                            <th>จำนวนเงิน</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($depositHistory as $deposit): ?>
                            <tr>
                                <td><?php echo $deposit['date']; ?> <?php echo $deposit['time']; ?></td>
                                <td><?php echo $deposit['method']; ?></td>
                                <td><?php echo $deposit['reference']; ?></td>
                                <td><?php echo number_format($deposit['amount'], 2); ?> บาท</td>
                                <td>
                                    <span class="badge <?php echo getStatusClass($deposit['status']); ?>">
                                        <?php echo getStatusText($deposit['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.table th, .table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    display: inline-block;
}
</style>

<?php
// แสดงส่วน footer
renderFooter();
?>