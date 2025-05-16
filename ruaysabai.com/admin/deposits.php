<?php
// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    // ถ้าไม่ได้ล็อกอินหรือไม่ใช่แอดมิน ให้ redirect ไปหน้า login
    $_SESSION['redirect_after_login'] = '/admin/';
    $_SESSION['login_message'] = 'กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ';
    header('Location: ../login.php');
    exit;
}

// นำเข้าไฟล์การตั้งค่า
require_once '../config.php';

// ถ้ามีข้อความแจ้งเตือน
$adminMessage = '';
if (isset($_SESSION['admin_message'])) {
    $adminMessage = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// ดึงข้อมูลการเติมเงิน
try {
    $db = connectDB();
    
    // ตรวจสอบการค้นหา
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    // สร้างเงื่อนไขการค้นหา
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(d.reference LIKE ? OR u.username LIKE ? OR u.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($status)) {
        $whereConditions[] = "d.status = ?";
        $params[] = $status;
    }
    
    if ($userId > 0) {
        $whereConditions[] = "d.user_id = ?";
        $params[] = $userId;
    }
    
    // สร้าง WHERE clause
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = "WHERE " . implode(' AND ', $whereConditions);
    }
    
    // กำหนดการแบ่งหน้า
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // คำสั่ง SQL สำหรับนับจำนวนรายการทั้งหมด
    $countSql = "SELECT COUNT(*) FROM deposits d JOIN users u ON d.user_id = u.id $whereClause";
    $countStmt = $db->prepare($countSql);
    if (!empty($params)) {
        $countStmt->execute($params);
    } else {
        $countStmt->execute();
    }
    $totalDeposits = $countStmt->fetchColumn();
    $totalPages = ceil($totalDeposits / $limit);
    
    // คำสั่ง SQL สำหรับดึงข้อมูลการเติมเงิน
    $sql = "SELECT d.*, u.username, u.name FROM deposits d JOIN users u ON d.user_id = u.id $whereClause ORDER BY d.created_at DESC LIMIT ?, ?";
    $stmt = $db->prepare($sql);
    
    // เพิ่มพารามิเตอร์สำหรับ LIMIT
    $paramCount = count($params);
    for ($i = 0; $i < $paramCount; $i++) {
        $stmt->bindParam($i + 1, $params[$i]);
    }
    $stmt->bindParam($paramCount + 1, $offset, PDO::PARAM_INT);
    $stmt->bindParam($paramCount + 2, $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $deposits = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
}

// ดึงจำนวนรายการตามสถานะ
try {
    $db = connectDB();
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM deposits GROUP BY status");
    $statusCounts = [];
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
    
    $pendingCount = $statusCounts['pending'] ?? 0;
    $successCount = $statusCounts['success'] ?? 0;
    $failedCount = $statusCounts['failed'] ?? 0;
    $totalCount = $totalDeposits;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบการเติมเงิน - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- เมนูด้านข้าง -->
        <?php include 'sidebar.php'; ?>
        
        <!-- เนื้อหาหลัก -->
        <div class="admin-content">
            <?php if (!empty($adminMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $adminMessage; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <h1>ตรวจสอบการเติมเงิน</h1>
            
            <!-- สรุปสถานะ -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>รอตรวจสอบ</h3>
                    <div class="value"><?php echo number_format($pendingCount); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>สำเร็จ</h3>
                    <div class="value"><?php echo number_format($successCount); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>ไม่สำเร็จ</h3>
                    <div class="value"><?php echo number_format($failedCount); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>ทั้งหมด</h3>
                    <div class="value"><?php echo number_format($totalCount); ?></div>
                </div>
            </div>
            
            <!-- ฟอร์มค้นหา -->
            <div class="card">
                <form action="" method="GET" class="search-form">
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <div class="form-group" style="flex: 2;">
                            <input type="text" name="search" class="form-control" placeholder="ค้นหาด้วยชื่อผู้ใช้หรือเลขอ้างอิง" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <select name="status" class="form-control">
                                <option value="">ทุกสถานะ</option>
                                <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>รอตรวจสอบ</option>
                                <option value="success" <?php echo isset($_GET['status']) && $_GET['status'] === 'success' ? 'selected' : ''; ?>>สำเร็จ</option>
                                <option value="failed" <?php echo isset($_GET['status']) && $_GET['status'] === 'failed' ? 'selected' : ''; ?>>ไม่สำเร็จ</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="flex: 0 0 auto;">
                            <button type="submit" class="btn">ค้นหา</button>
                            <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['user_id'])): ?>
                                <a href="deposits.php" class="btn btn-secondary">ล้างการค้นหา</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['user_id'])): ?>
                        <input type="hidden" name="user_id" value="<?php echo (int)$_GET['user_id']; ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- ตารางการเติมเงิน -->
            <div class="card">
                <h2 class="card-title">รายการเติมเงิน</h2>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ผู้ใช้</th>
                                <th>วันที่</th>
                                <th>วิธีการเติมเงิน</th>
                                <th>อ้างอิง</th>
                                <th>จำนวนเงิน</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">ไม่พบข้อมูลการเติมเงิน</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $deposit): ?>
                                <tr>
                                    <td><?php echo $deposit['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($deposit['username']); ?></div>
                                        <div class="text-muted"><?php echo htmlspecialchars($deposit['name']); ?></div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($deposit['created_at'])); ?></td>
                                    <td><?php echo $payment_methods[$deposit['method']]; ?></td>
                                    <td><?php echo htmlspecialchars($deposit['reference']); ?></td>
                                    <td><?php echo number_format($deposit['amount'], 2); ?> บาท</td>
                                    <td>
                                        <span class="badge <?php echo getStatusClass($deposit['status']); ?>">
                                            <?php echo getStatusText($deposit['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($deposit['status'] === 'pending'): ?>
                                        <a href="verify_deposit.php?id=<?php echo $deposit['id']; ?>" class="btn btn-sm">ตรวจสอบ</a>
                                        <?php else: ?>
                                        <a href="view_deposit.php?id=<?php echo $deposit['id']; ?>" class="btn btn-sm">ดูรายละเอียด</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- การแบ่งหน้า -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php 
                    // สร้าง query string สำหรับการแบ่งหน้า
                    $queryParams = [];
                    if (!empty($search)) $queryParams['search'] = $search;
                    if (!empty($status)) $queryParams['status'] = $status;
                    if ($userId > 0) $queryParams['user_id'] = $userId;
                    
                    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1 . $queryString; ?>" class="btn">หน้าก่อนหน้า</a>
                    <?php endif; ?>
                    
                    <?php 
                    // แสดงปุ่มหน้าไม่เกิน 5 ปุ่ม
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="?page=<?php echo $i . $queryString; ?>" class="btn <?php echo $i == $page ? 'btn-accent' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1 . $queryString; ?>" class="btn">หน้าถัดไป</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>