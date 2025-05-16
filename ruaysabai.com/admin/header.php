<?php
// ตรวจสอบการล็อกอิน และสิทธิ์แอดมิน
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php?redirect=admin');
    exit();
}

// เชื่อมต่อฐานข้อมูล
require_once('../config.php');

// ข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset($db_config['charset']);

// ดึงข้อมูลผู้ใช้
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

// ดึงข้อมูลการแจ้งเตือน
$notifications_sql = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
$notifications_result = $conn->query($notifications_sql);
$notifications_count = $notifications_result->fetch_assoc()['count'];

// ดึงข้อมูลการเติมเงินที่รอตรวจสอบ
$deposits_sql = "SELECT COUNT(*) as count FROM transactions WHERE type = 'deposit' AND status = 'pending'";
$deposits_result = $conn->query($deposits_sql);
$pending_deposits = $deposits_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการหลังบ้าน - หวยออนไลน์</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.05);
            background-color: #343a40;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .navbar {
            box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.05);
            background-color: #0d6efd!important;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8)!important;
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
        }
        
        .nav-link:hover {
            color: #fff!important;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-link.active {
            color: #fff!important;
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }
        
        .nav-link .badge {
            margin-left: 5px;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1.1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }
        
        .card-header {
            background-color: #f8f9fa;
            font-weight: bold;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .notification-badge {
            position: absolute;
            top: 0.4rem;
            right: 0.4rem;
            padding: 0.2rem 0.4rem;
            border-radius: 50%;
            font-size: 0.75rem;
        }
    </style>
    
    <!-- Google Font - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
            <i class="bi bi-lightning-charge-fill"></i> ระบบหลังบ้าน
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Search Bar -->
        <div class="w-100 d-none d-md-block">
            <form class="w-50 mx-auto">
                <input class="form-control form-control-dark" type="text" placeholder="ค้นหา..." aria-label="Search">
            </form>
        </div>
        
        <!-- Navbar Right -->
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3 position-relative">
                    <a class="nav-link px-3 text-white" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($notifications_count > 0): ?>
                        <span class="badge bg-danger notification-badge"><?php echo $notifications_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 300px;">
                        <li><h6 class="dropdown-header">การแจ้งเตือน</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <?php if ($pending_deposits > 0): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="deposits.php">
                                <div class="me-3">
                                    <div class="bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-cash-coin text-white"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500">เมื่อ <?php echo date('d/m/Y H:i'); ?></div>
                                    <span>มีการเติมเงิน <?php echo $pending_deposits; ?> รายการที่รอการตรวจสอบ</span>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        
                        <li><a class="dropdown-item text-center small text-gray-500" href="notifications.php">แสดงการแจ้งเตือนทั้งหมด</a></li>
                    </ul>
                </div>
                
                <!-- Profile -->
                <div class="dropdown me-3">
                    <a class="nav-link px-3 text-white d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($user_data['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile" class="avatar me-2">
                        <?php else: ?>
                        <i class="bi bi-person-circle me-2" style="font-size: 1.2rem;"></i>
                        <?php endif; ?>
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($user_data['username']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-fill me-2"></i> โปรไฟล์</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear-fill me-2"></i> ตั้งค่า</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
