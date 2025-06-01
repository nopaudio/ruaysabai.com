<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ตรวจสอบข้อมูล
    if (!$username || !$full_name || !$email || !$password) {
        $errorMsg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errorMsg = 'Username ต้องเป็นตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ เท่านั้น (3-20 ตัวอักษร)';
    } else {
        try {
            $db = Database::getInstance();
            
            // ตรวจสอบ username ซ้ำ
            $existUser = $db->select("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            
            if (!empty($existUser)) {
                $errorMsg = 'Username หรือ Email นี้ถูกใช้แล้ว';
            } else {
                // สร้าง hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // บันทึกข้อมูลผู้ใช้ใหม่
                $result = $db->insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'password_hash' => $password_hash,
                    'full_name' => $full_name,
                    'user_type' => 'free',
                    'member_type' => 'free',
                    'status' => 'active',
                    'credits' => 10,
                    'daily_limit' => 10
                ]);

                if ($result) {
                    // สมัครสำเร็จ - redirect ไปหน้า login
                    header('Location: login.php?reg=success');
                    exit;
                } else {
                    $errorMsg = 'เกิดข้อผิดพลาด ไม่สามารถสมัครสมาชิกได้';
                }
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $errorMsg = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>สมัครสมาชิก | AI Prompt Generator</title>
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
        .register-container {
            max-width: 450px;
            margin: 60px auto;
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
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .register-header p {
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
        .btn-register {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 480px) {
            .register-container { 
                margin: 20px;
                padding: 30px 20px;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>สมัครสมาชิก</h2>
            <p>เข้าร่วมกับเรา เพื่อใช้งาน AI Prompt Generator</p>
        </div>
        
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="register.php" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" name="username" class="form-control" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       placeholder="เช่น john_doe"
                       pattern="[a-zA-Z0-9_]{3,20}"
                       title="ใช้ได้เฉพาะตัวอักษรภาษาอังกฤษ ตัวเลข และ _ (3-20 ตัวอักษร)"
                       required autofocus>
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-id-card"></i> ชื่อ-สกุล
                </label>
                <input type="text" name="full_name" class="form-control" 
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                       placeholder="ชื่อจริง นามสกุล"
                       required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="your-email@example.com"
                       required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" name="password" class="form-control" 
                       placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร"
                       minlength="6"
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-register">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </button>
        </form>
        
        <div class="login-link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a> | 
            <a href="index.php">กลับหน้าหลัก</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>