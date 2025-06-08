<?php
// login.php แบบง่าย - แก้ปัญหาหน้าขาว
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$errorMsg = '';
$successMsg = '';

// ตรวจสอบข้อความ logout
if (isset($_SESSION['logout_success'])) {
    $successMsg = 'คุณออกจากระบบเรียบร้อยแล้ว';
    unset($_SESSION['logout_success']);
}

// ตรวจสอบ redirect loop - ถ้า login แล้วและไม่ใช่ POST ให้ redirect
if (isUserLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $redirect = $_GET['redirect'] ?? 'profile.php';
    
    // ป้องกัน redirect loop
    if (!strpos($redirect, 'login.php')) {
        header('Location: ' . $redirect);
        exit;
    } else {
        header('Location: profile.php');
        exit;
    }
}

// ประมวลผล Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $errorMsg = 'กรุณากรอก Username/Email และรหัสผ่าน';
    } else {
        try {
            $db = Database::getInstance();
            
            $user_result = $db->select(
                "SELECT id, username, email, password_hash, full_name, user_type, member_type, status, expire_date, points_balance 
                 FROM users 
                 WHERE (username = ? OR email = ?) AND status = 'active' 
                 LIMIT 1", 
                [$login, $login]
            );
            
            $user = $user_result[0] ?? null;
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errorMsg = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                error_log("Failed login attempt for: " . $login);
            } else {
                // Login สำเร็จ
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['login_time'] = time();
                
                // อัปเดต last_login
                $db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                
                // ตรวจสอบ redirect
                $redirect = $_GET['redirect'] ?? 'profile.php';
                $safe_redirect = 'profile.php';
                
                if (!empty($redirect) && !strpos($redirect, 'login.php') && !strpos($redirect, 'logout.php')) {
                    if (preg_match('/^[a-zA-Z0-9_.\/-]+\.php([?&][a-zA-Z0-9_=&%-]*)?$/', $redirect)) {
                        $safe_redirect = $redirect;
                    }
                }
                
                error_log("Successful login for user: " . $user['username'] . " (ID: " . $user['id'] . ")");
                
                header('Location: ' . $safe_redirect);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 30px 35px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(8px);
        }
        .logo {
            display: block;
            margin: 0 auto 20px auto;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-header h2 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #566573;
            font-size: 0.95rem;
        }
        .form-control {
            border-radius: 10px;
            border: 1.5px solid #d4dae3;
            padding: 12px 18px;
            font-size: 1rem;
            transition: all 0.25s ease-in-out;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3.5px rgba(102, 126, 234, 0.15);
            background-color: #fff;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #5461c8 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1.05rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        .btn-login:hover, .btn-login:focus {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 25px rgba(84, 97, 200, 0.3);
            background: linear-gradient(135deg, #5a67d8 0%, #4a56b8 100%);
        }
        .alert {
            border-radius: 8px;
            font-size: 0.9rem;
        }
        .alert-danger {
             background-color: #fdeded;
             color: #c0392b;
             border: 1px solid #f5b7b1;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
            font-size: 0.9rem;
        }
        .register-link a {
            color: #5a67d8;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
            text-decoration: underline;
            color: #4351b0;
        }
        .input-group-text {
            background-color: #e9ecef;
            border: 1.5px solid #d4dae3;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .form-control-with-icon {
             border-left: none;
             border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-brain"></i>
            </div>
            <h2>เข้าสู่ระบบ</h2>
            <p>จัดการ Prompt ของคุณได้ง่ายๆ</p>
        </div>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['reg']) && $_GET['reg'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars(urlencode($_GET['redirect'])) : ''; ?>" autocomplete="on">
            <div class="mb-3">
                <label for="login-input" class="form-label">Username หรือ Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="login" id="login-input" class="form-control form-control-with-icon" 
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                           placeholder="กรอก username หรือ email"
                           required autofocus>
                </div>
            </div>
            
            <div class="mb-4">
                 <label for="password-input" class="form-label">Password</label>
                 <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password-input" class="form-control form-control-with-icon" 
                           placeholder="กรอกรหัสผ่าน"
                           required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="register-link">
            <a href="forgot_password.php">ลืมรหัสผ่าน?</a> | 
            <a href="register.php">ยังไม่มีบัญชี? สมัครเลย</a>
            <div style="margin-top: 15px;">
                <a href="index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>