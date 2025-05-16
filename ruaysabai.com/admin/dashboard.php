<?php
// แสดงข้อผิดพลาดทั้งหมด (ใช้เฉพาะตอนพัฒนา)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่มต้น session
session_start();

// ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header('Location: index.php');
    exit();
}

try {
    // นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
    require_once('../config.php');

    // สร้างการเชื่อมต่อกับฐานข้อมูล
    $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
    }

    // กำหนด charset เป็น utf8
    $conn->set_charset($db_config['charset']);

    // ฟังก์ชันสำหรับตรวจสอบว่าตารางมีอยู่หรือไม่
    function tableExists($conn, $tableName) {
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->num_rows > 0;
    }

    // ฟังก์ชันสำหรับตรวจสอบว่าคอลัมน์มีอยู่ในตารางหรือไม่
    function columnExists($conn, $tableName, $columnName) {
        $result = $conn->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
        return $result->num_rows > 0;
    }

    // 1. จำนวนผู้ใช้ทั้งหมด
    $userCount = 0;
    if (tableExists($conn, 'users')) {
        // ตรวจสอบว่ามีคอลัมน์ role หรือไม่
        if (columnExists($conn, 'users', 'role')) {
            $userCountQuery = "SELECT COUNT(*) AS user_count FROM users WHERE role = 'user'";
        } else {
            // ถ้าไม่มีคอลัมน์ role ให้นับทุกบัญชีที่ไม่ใช่ admin
            $userCountQuery = "SELECT COUNT(*) AS user_count FROM users WHERE username != 'admin'";
        }
        $userResult = $conn->query($userCountQuery);
        $userCount = $userResult->fetch_assoc()['user_count'];
    }

    // 2. จำนวนเงินในระบบทั้งหมด
    $totalBalance = 0;
    if (tableExists($conn, 'users') && columnExists($conn, 'users', 'balance')) {
        $totalBalanceQuery = "SELECT SUM(balance) AS total_balance FROM users";
        $balanceResult = $conn->query($totalBalanceQuery);
        $totalBalance = $balanceResult->fetch_assoc()['total_balance'] ?: 0;
    }

    // 3. จำนวนการเติมเงิน 7 วันล่าสุด
    $depositCount = 0;
    if (tableExists($conn, 'transactions')) {
        $transactionTypeColumn = columnExists($conn, 'transactions', 'transaction_type');
        if ($transactionTypeColumn) {
            $recentDepositsQuery = "SELECT COUNT(*) AS deposit_count FROM transactions 
                                WHERE transaction_type = 'deposit' 
                                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } else {
            // ถ้าไม่มีคอลัมน์ transaction_type ให้ตรวจสอบว่ามีคอลัมน์อื่นที่ใช้ได้หรือไม่
            // เช่น type หรือ action
            if (columnExists($conn, 'transactions', 'type')) {
                $recentDepositsQuery = "SELECT COUNT(*) AS deposit_count FROM transactions 
                                    WHERE type = 'deposit' 
                                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } else if (columnExists($conn, 'transactions', 'action')) {
                $recentDepositsQuery = "SELECT COUNT(*) AS deposit_count FROM transactions 
                                    WHERE action = 'deposit' 
                                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } else {
                // ถ้าไม่มีคอลัมน์ใดๆ ที่ระบุประเภทการทำรายการ ให้นับทั้งหมด
                $recentDepositsQuery = "SELECT COUNT(*) AS deposit_count FROM transactions 
                                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            }
        }
        $depositResult = $conn->query($recentDepositsQuery);
        $depositCount = $depositResult->fetch_assoc()['deposit_count'];
    }

    // 4. จำนวนการซื้อหวย 7 วันล่าสุด
    $purchaseCount = 0;
    if (tableExists($conn, 'lottery_tickets')) {
        $recentPurchasesQuery = "SELECT COUNT(*) AS purchase_count FROM lottery_tickets 
                             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $purchaseResult = $conn->query($recentPurchasesQuery);
        $purchaseCount = $purchaseResult->fetch_assoc()['purchase_count'];
    }

    // 5. ข้อมูลการเติมเงินที่รอการอนุมัติล่าสุด
    $pendingDepositsResult = null;
    if (tableExists($conn, 'deposits') && tableExists($conn, 'users')) {
        // ตรวจสอบว่ามีคอลัมน์ status ในตาราง deposits หรือไม่
        if (columnExists($conn, 'deposits', 'status')) {
            $pendingDepositsQuery = "SELECT d.*, u.username 
                                FROM deposits d 
                                JOIN users u ON d.user_id = u.id 
                                WHERE d.status = 'pending' 
                                ORDER BY d.created_at DESC 
                                LIMIT 5";
            $pendingDepositsResult = $conn->query($pendingDepositsQuery);
        }
    }

    // 6. ผู้ใช้ที่สมัครใหม่ล่าสุด
    $newUsersResult = null;
    if (tableExists($conn, 'users')) {
        // ตรวจสอบว่ามีคอลัมน์ role หรือไม่
        if (columnExists($conn, 'users', 'role')) {
            $newUsersQuery = "SELECT * FROM users 
                         WHERE role = 'user' 
                         ORDER BY created_at DESC 
                         LIMIT 5";
        } else {
            // ถ้าไม่มีคอลัมน์ role ให้ดึงทุกบัญชีที่ไม่ใช่ admin
            $newUsersQuery = "SELECT * FROM users 
                         WHERE username != 'admin' 
                         ORDER BY created_at DESC 
                         LIMIT 5";
        }
        $newUsersResult = $conn->query($newUsersQuery);
    }
} catch (Exception $e) {
    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .content {
            flex: 1;
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .stat-card {
            text-align: center;
            padding: 15px;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            font-size: 1rem;
            color: #6c757d;
        }
        
        .user-icon {
            color: #007bff;
        }
        
        .money-icon {
            color: #28a745;
        }
        
        .deposit-icon {
            color: #17a2b8;
        }
        
        .ticket-icon {
            color: #ffc107;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .alert {
            border-radius: 5px;
        }
        
        .welcome-message {
            padding: 20px;
            background-color: #343a40;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <div class="container-fluid">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="welcome-message">
                    <h2><i class="fas fa-tachometer-alt"></i> แดชบอร์ด</h2>
                    <p>ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['username']); ?>! ตรวจสอบภาพรวมของระบบได้ที่นี่</p>
                </div>
                
                <!-- สถิติสรุป -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="fas fa-users user-icon"></i>
                            <div class="stat-value"><?php echo number_format($userCount); ?></div>
                            <div class="stat-label">ผู้ใช้ทั้งหมด</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="fas fa-wallet money-icon"></i>
                            <div class="stat-value"><?php echo number_format($totalBalance, 2); ?></div>
                            <div class="stat-label">เงินในระบบทั้งหมด (บาท)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="fas fa-money-bill-wave deposit-icon"></i>
                            <div class="stat-value"><?php echo number_format($depositCount); ?></div>
                            <div class="stat-label">การเติมเงิน 7 วันล่าสุด</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <i class="fas fa-ticket-alt ticket-icon"></i>
                            <div class="stat-value"><?php echo number_format($purchaseCount); ?></div>
                            <div class="stat-label">การซื้อหวย 7 วันล่าสุด</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- การเติมเงินที่รอการอนุมัติ -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">การเติมเงินที่รอการอนุมัติล่าสุด</h5>
                                <a href="deposits.php" class="btn btn-sm btn-outline-light">ดูทั้งหมด</a>
                            </div>
                            <div class="card-body">
                                <?php if ($pendingDepositsResult && $pendingDepositsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ผู้ใช้</th>
                                                <th>จำนวนเงิน</th>
                                                <th>วิธีการ</th>
                                                <th>เวลา</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($deposit = $pendingDepositsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($deposit['username']); ?></td>
                                                <td><?php echo number_format($deposit['amount'], 2); ?> บาท</td>
                                                <td>
                                                    <?php 
                                                    $paymentMethod = isset($deposit['payment_method']) ? $deposit['payment_method'] : '';
                                                    switch($paymentMethod) {
                                                        case 'bank':
                                                            echo '<span class="badge badge-primary">ธนาคาร</span>';
                                                            break;
                                                        case 'promptpay':
                                                            echo '<span class="badge badge-success">พร้อมเพย์</span>';
                                                            break;
                                                        case 'truemoney':
                                                            echo '<span class="badge badge-danger">ทรูมันนี่</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">อื่นๆ</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($deposit['created_at'])); ?></td>
                                                <td>
                                                    <a href="deposits.php?view=<?php echo $deposit['id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    ไม่มีการเติมเงินที่รอการอนุมัติในขณะนี้
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ผู้ใช้ใหม่ล่าสุด -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">ผู้ใช้ที่สมัครใหม่ล่าสุด</h5>
                                <a href="users.php" class="btn btn-sm btn-outline-light">ดูทั้งหมด</a>
                            </div>
                            <div class="card-body">
                                <?php if ($newUsersResult && $newUsersResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ชื่อผู้ใช้</th>
                                                <th>อีเมล</th>
                                                <th>เบอร์โทร</th>
                                                <th>วันที่สมัคร</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = $newUsersResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : '-'; ?></td>
                                                <td><?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : '-'; ?></td>
                                                <td><?php echo isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-'; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info">
                                    ไม่มีผู้ใช้ใหม่ในขณะนี้
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ปุ่มลัดไปยังหน้าอื่นๆ -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <a href="lottery-results.php" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fas fa-clipboard-list"></i> จัดการผลหวย
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="deposits.php" class="btn btn-success btn-lg btn-block mb-3">
                            <i class="fas fa-money-check-alt"></i> อนุมัติการเติมเงิน
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="settings.php" class="btn btn-info btn-lg btn-block mb-3">
                            <i class="fas fa-cogs"></i> ตั้งค่าระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn)) {
    $conn->close();
}
?>