<?php
// เริ่มต้น session
session_start();

// รวมไฟล์การตั้งค่า
require_once 'config.php';

// ตรวจสอบว่ามีการล็อกอินอยู่หรือไม่
if(isset($_SESSION['user_id'])) {
    // ถ้าเป็นแอดมิน ให้ redirect ไปยังหน้าแอดมิน
    if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location: admin/index.php');
        exit;
    }
}

// การตรวจสอบ redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$redirect_message = '';

if($redirect === 'admin') {
    $redirect_message = 'กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบเพื่อเข้าถึงหน้าแอดมิน';
}

// ตรวจสอบการส่งแบบฟอร์ม
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = '';
    
    // ตรวจสอบข้อมูลว่างเปล่า
    if(empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        // เชื่อมต่อฐานข้อมูล
        $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
        $conn->set_charset($db_config['charset']);
        
        // ตรวจสอบการเชื่อมต่อ
        if ($conn->connect_error) {
            die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
        }
        
        // ค้นหาผู้ใช้จากฐานข้อมูล
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // ตรวจสอบรหัสผ่าน
            if(password_verify($password, $user['password'])) {
                // รหัสผ่านถูกต้อง ตั้งค่า session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // อัปเดตเวลาเข้าสู่ระบบล่าสุด (ถ้ามีฟิลด์ last_login)
                if ($conn->query("SHOW COLUMNS FROM users LIKE 'last_login'")->num_rows > 0) {
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // Redirect ตามบทบาท
                if($user['is_admin'] == 1) {
                    header('Location: admin/index.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบหวยออนไลน์</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .login-form button {
            width: 100%;
            background-color: #3498db;
            color: white;
            padding: 14px 20px;
            margin: 8px 0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .login-form button:hover {
            background-color: #2980b9;
        }
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        .login-links a {
            color: #3498db;
            text-decoration: none;
            margin: 0 10px;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }
        .redirect-message {
            color: #e67e22;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f2e6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php if(file_exists('header.php')) include 'header.php'; ?>
    
    <div class="main-content">
        <div class="login-container">
            <div class="login-logo">
                <h2>เข้าสู่ระบบ</h2>
            </div>
            
            <?php if(!empty($redirect_message)): ?>
            <div class="redirect-message">
                <?php echo htmlspecialchars($redirect_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error) && !empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="post">
                <div>
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div>
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">เข้าสู่ระบบ</button>
            </form>
            
            <div class="login-links">
                <?php if(file_exists('register.php')): ?>
                <a href="register.php">สมัครสมาชิกใหม่</a> | 
                <?php endif; ?>
                <?php if(file_exists('forgot-password.php')): ?>
                <a href="forgot-password.php">ลืมรหัสผ่าน</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if(file_exists('footer.php')) include 'footer.php'; ?>

    <!-- เพิ่มลิงก์ไปยังหน้าตรวจสอบ session สำหรับผู้ดูแลระบบ -->
    <div style="text-align: center; margin-top: 20px; font-size: 12px;">
        <a href="check_session.php" style="color: #999; text-decoration: none;">ตรวจสอบสถานะ session</a>
    </div>
</body>
</html>