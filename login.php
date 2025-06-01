<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$errorMsg = '';

// ตรวจสอบว่าล็อกอินแล้วหรือยัง
if (isUserLoggedIn()) {
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$login || !$password) {
        $errorMsg = 'กรุณากรอก Username/Email และรหัสผ่าน';
    } else {
        try {
            $db = Database::getInstance();
            
            // ค้นหาผู้ใช้จาก username หรือ email
            $user = $db->select("SELECT * FROM users WHERE username = ? OR email = ?", [$login, $login]);
            $user = $user[0] ?? null;
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errorMsg = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            } elseif ($user['status'] !== 'active') {
                $errorMsg = 'บัญชีนี้ถูกระงับ กรุณาติดต่อผู้ดูแลระบบ';
            } else {
                // ล็อกอินสำเร็จ
                $_SESSION['user_id'] = $user['id'];
                
                // อัปเดตเวลาล็อกอินล่าสุด
                $db->update('users', [
                    'last_login' => date('Y-m-d H:i:s')
                ], "id = " . $user['id']);
                
                // ไปหน้าที่ต้องการ
                $redirect = $_GET['redirect'] ?? 'profile.php';
                header('Location: ' . $redirect);
                exit;
            }
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $errorMsg = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>เข้าสู่ระบบ | AI Prompt Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 420px;
            margin: 80px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .logo {
            display: block;
            margin: 0 auto 20px auto;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #059669;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        @media (max-width: 480px) {
            .login-container { 
                margin: 20px;
                padding: 30px 20px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-sign-in-alt"></i>
            </div>
            <h2>เข้าสู่ระบบ</h2>
            <p>ยินดีต้อนรับกลับมา!</p>
        </div>
        
        <?php if (!empty($_GET['reg']) && $_GET['reg'] == 'success'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php" autocomplete="on">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Username หรือ Email
                </label>
                <input type="text" name="login" class="form-control" 
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                       placeholder="กรอก username หรือ email"
                       required autofocus>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" name="password" class="form-control" 
                       placeholder="กรอกรหัสผ่าน"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="register-link">
            <a href="forgot_password.php">ลืมรหัสผ่าน?</a> | 
            <a href="register.php">สมัครสมาชิก</a>
            <br><br>
            <a href="index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>