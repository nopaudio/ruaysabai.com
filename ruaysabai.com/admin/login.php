<?php
/**
 * Enhanced Admin Login System for Ruay Sabai Lottery Website
 * 
 * This script provides a secure login mechanism for the admin section, 
 * including brute force protection, secure session handling, and audit logging.
 */

// Include the security enhancements
require_once 'admin_security.php';

// Start secure session
if (session_status() == PHP_SESSION_NONE) {
    // Session is started in admin_security.php
    session_start();
}

// Enable HTTPS in production
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    // Already using HTTPS
} else {
    // Uncomment in production to force HTTPS
    // header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    // exit();
}

// Check if user is already logged in
if (isset($_SESSION['admin_id'])) {
    // Redirect to dashboard if already logged in
    header('Location: dashboard.php');
    exit;
}

// Process login form submission
$login_error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !$adminSecurity->verifyCsrfToken($_POST['csrf_token'], 'admin_login')) {
        $login_error = "การยืนยันความปลอดภัยล้มเหลว กรุณาลองใหม่อีกครั้ง";
    } else {
        // Get and sanitize input
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Don't sanitize password
        
        // Validate input
        if (empty($username) || empty($password)) {
            $login_error = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        } else {
            // Authenticate user
            $admin = $adminSecurity->authenticate($username, $password);
            
            if ($admin) {
                // Set admin session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                $_SESSION['is_admin'] = true;
                
                // Log successful login
                $adminSecurity->logAdminActivity('login', 'Admin logged in successfully', 'auth');
                
                // Redirect to dashboard or requested page
                $redirect = $_SESSION['redirect_url'] ?? 'dashboard.php';
                unset($_SESSION['redirect_url']);
                
                header("Location: $redirect");
                exit;
            } else {
                $login_error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
            }
        }
    }
}

// Get message from session if exists
$setup_message = '';
if (isset($_SESSION['setup_message'])) {
    $setup_message = $_SESSION['setup_message'];
    unset($_SESSION['setup_message']);
}

if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>เข้าสู่ระบบหลังบ้าน - เว็บหวยรวยสบาย</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.png" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #4527a0, #7953d2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .login-header {
            background-color: #343a40;
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .login-logo {
            margin-bottom: 15px;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: #ff9800;
        }
        
        .login-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-form {
            padding: 30px;
            background-color: white;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 152, 0, 0.25);
            border-color: #ff9800;
        }
        
        .btn-login {
            background-color: #ff9800;
            border-color: #ff9800;
            color: white;
            font-weight: 600;
            padding: 12px 0;
            transition: all 0.3s;
        }
        
        .btn-login:hover, .btn-login:focus {
            background-color: #e68a00;
            border-color: #e68a00;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .login-footer a {
            color: #6c757d;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: #343a40;
        }
        
        .alert {
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .form-floating > label {
            font-size: 0.9rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            color: #ff9800;
        }
        
        .security-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .bounce-animation {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        /* Login attempt counter */
        #login-attempts {
            position: absolute;
            bottom: 5px;
            right: 10px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Password toggle button */
        .password-toggle {
            cursor: pointer;
            padding: 0 0.75rem;
        }
        
        /* Two-factor auth section */
        .two-factor-auth {
            display: none;
            margin-top: 1rem;
        }
        
        /* OTP input style */
        .otp-input {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
        }
        
        .otp-input input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0 0.25rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        
        .otp-input input:focus {
            border-color: #ff9800;
            box-shadow: 0 0 0 0.25rem rgba(255, 152, 0, 0.25);
            outline: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-lock bounce-animation"></i>
            </div>
            <h4><i class="fas fa-user-shield me-2"></i> ระบบหลังบ้าน</h4>
            <div id="login-attempts"><?php /* This will be filled by JavaScript */ ?></div>
        </div>
        
        <div class="login-form">
            <?php if (!empty($setup_message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo htmlspecialchars($setup_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($login_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                <!-- CSRF token -->
                <?php echo $adminSecurity->csrfTokenField('admin_login'); ?>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้" 
                           value="<?php echo htmlspecialchars($username); ?>" required>
                    <label for="username"><i class="fas fa-user me-2"></i>ชื่อผู้ใช้</label>
                </div>
                
                <div class="form-floating mb-3">
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                        <span class="input-group-text password-toggle" id="password-toggle">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <label for="password" style="z-index: 3; margin-left: 12px;"><i class="fas fa-key me-2"></i>รหัสผ่าน</label>
                </div>
                
                <!-- Two-factor authentication section (hidden by default) -->
                <div class="two-factor-auth" id="two-factor-auth">
                    <div class="alert alert-warning">
                        <i class="fas fa-shield-alt me-2"></i> กรุณากรอกรหัส OTP ที่ส่งไปยังอีเมลหรือโทรศัพท์ของคุณ
                    </div>
                    
                    <div class="otp-input">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-1" class="form-control" required>
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-2" class="form-control" required>
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-3" class="form-control" required>
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-4" class="form-control" required>
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-5" class="form-control" required>
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" name="otp[]" id="otp-6" class="form-control" required>
                    </div>
                    
                    <div class="text-center mb-3">
                        <button type="button" id="resend-otp" class="btn btn-link">ส่งรหัส OTP ใหม่</button>
                        <div id="otp-timer" class="small text-muted"></div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                    </button>
                </div>
                
                <div class="security-info">
                    <i class="fas fa-shield-alt me-1"></i> ระบบนี้มีการป้องกันการล็อกอินที่ไม่ได้รับอนุญาต หากพบการพยายามเข้าสู่ระบบที่ผิดปกติ บัญชีอาจถูกระงับชั่วคราว
                </div>
            </form>
        </div>
        
        <div class="login-footer p-3 bg-light">
            <a href="../index.php"><i class="fas fa-arrow-left me-1"></i> กลับไปยังหน้าหลัก</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password toggle functionality
        const passwordField = document.getElementById('password');
        const passwordToggle = document.getElementById('password-toggle');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Change the eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        // OTP input handling
        const otpInputs = document.querySelectorAll('.otp-input input');
        otpInputs.forEach((input, index) => {
            // Auto focus next input on entry
            input.addEventListener('input', function() {
                if (this.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
        
        // Login attempts counter
        let loginAttempts = localStorage.getItem('loginAttempts') || 0;
        document.getElementById('login-attempts').textContent = `${loginAttempts} ครั้ง`;
        
        // Increment login attempts on form submit
        document.getElementById('login-form').addEventListener('submit', function() {
            loginAttempts = parseInt(loginAttempts) + 1;
            localStorage.setItem('loginAttempts', loginAttempts);
        });
        
        // Resend OTP button with timer
        const resendOtpButton = document.getElementById('resend-otp');
        const otpTimer = document.getElementById('otp-timer');
        
        resendOtpButton.addEventListener('click', function() {
            this.disabled = true;
            
            // Simulate OTP resend with timer (60 seconds)
            let seconds = 60;
            otpTimer.textContent = `กรุณารอ ${seconds} วินาที ก่อนส่งรหัสใหม่`;
            
            const timer = setInterval(() => {
                seconds--;
                otpTimer.textContent = `กรุณารอ ${seconds} วินาที ก่อนส่งรหัสใหม่`;
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    this.disabled = false;
                    otpTimer.textContent = '';
                }
            }, 1000);
            
            // Here you would actually implement the OTP resend functionality
            // For demonstration purposes, we'll just show a success message
            setTimeout(() => {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> ส่งรหัส OTP ใหม่เรียบร้อยแล้ว
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.getElementById('two-factor-auth').appendChild(alert);
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            }, 2000);
        });
        
        // Page focus handling - for security
        let pageBlurredAt = 0;
        window.addEventListener('blur', function() {
            pageBlurredAt = new Date().getTime();
        });
        
        window.addEventListener('focus', function() {
            // If page was unfocused for more than 30 minutes, reload for security
            if (pageBlurredAt > 0 && (new Date().getTime() - pageBlurredAt) > 30 * 60 * 1000) {
                window.location.reload();
            }
        });
        
        // Add warning when trying to leave the page with entered data
        window.addEventListener('beforeunload', function(e) {
            const form = document.getElementById('login-form');
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (username.value || password.value) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    });
    </script>
</body>
</html>
