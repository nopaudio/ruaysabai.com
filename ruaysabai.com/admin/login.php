<?php
session_start();
require_once '../config.php';

// กระบวนการเข้าสู่ระบบแอดมิน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        try {
            // ตรวจสอบว่ามีตาราง admins หรือไม่
            $table_check = $conn->query("SHOW TABLES LIKE 'admins'");
            
            if ($table_check->num_rows == 0) {
                // ถ้าไม่มีตาราง admins ให้สร้างตารางและเพิ่มข้อมูล admin เริ่มต้น
                $create_table = "CREATE TABLE IF NOT EXISTS `admins` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `name` varchar(100) NOT NULL,
                    `email` varchar(100) DEFAULT NULL,
                    `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
                    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                
                $conn->query($create_table);
                
                // เพิ่ม admin เริ่มต้น (username: admin, password: admin1234)
                $admin_password = password_hash('admin1234', PASSWORD_DEFAULT);
                $insert_admin = "INSERT INTO `admins` (`username`, `password`, `name`, `email`, `role`, `status`) 
                                VALUES ('admin', '$admin_password', 'ผู้ดูแลระบบ', 'admin@ruaysabai.com', 'admin', 'active')";
                $conn->query($insert_admin);
                
                $_SESSION['setup_message'] = "ระบบได้สร้างบัญชีแอดมินเริ่มต้นแล้ว (username: admin, password: admin1234)";
            }
            
            // ตรวจสอบการเข้าสู่ระบบ
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // ตรวจสอบรหัสผ่าน
                if (password_verify($password, $admin['password'])) {
                    // เข้าสู่ระบบสำเร็จ
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // บันทึกเวลาเข้าสู่ระบบล่าสุด
                    $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $admin['id']);
                    $update_stmt->execute();
                    
                    // ไปยังหน้าแดชบอร์ด
                    header('Location: index.php');
                    exit();
                } else {
                    // รหัสผ่านไม่ถูกต้อง
                    $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
                }
            } else {
                // ไม่พบผู้ใช้
                $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ข้อความที่จะแสดง
$login_error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
$setup_message = isset($_SESSION['setup_message']) ? $_SESSION['setup_message'] : '';

// ล้างข้อความ
unset($_SESSION['login_error']);
unset($_SESSION['setup_message']);

// ตรวจสอบการเข้าสู่ระบบ ถ้าเข้าสู่ระบบแล้วให้ไปหน้าแดชบอร์ด
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบหลังบ้าน - เว็บหวยรวยสบาย</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            max-width: 150px;
        }
        
        .login-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            background-color: #343a40;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        
        .login-form {
            padding: 20px;
        }
        
        .btn-primary {
            background-color: #343a40;
            border-color: #343a40;
        }
        
        .btn-primary:hover {
            background-color: #23272b;
            border-color: #23272b;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h2>เว็บหวยรวยสบาย</h2>
        </div>
        
        <?php if ($setup_message): ?>
        <div class="alert alert-info" role="alert">
            <?php echo $setup_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($login_error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $login_error; ?>
        </div>
        <?php endif; ?>
        
        <div class="card login-card">
            <div class="login-header">
                <h4><i class="fas fa-lock"></i> เข้าสู่ระบบหลังบ้าน</h4>
            </div>
            <div class="login-form">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-3 text-center">
            <a href="../index.php" class="text-decoration-none"><i class="fas fa-arrow-left"></i> กลับไปยังหน้าเว็บไซต์</a>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
