<?php
// นำเข้าไฟล์โครงสร้างพื้นฐาน
require_once 'structure.php';

// ตรวจสอบว่ามีการล็อกอินแล้วหรือไม่
if ($isLoggedIn) {
    header('Location: index.php');
    exit;
}

// สร้างข้อมูลผู้ใช้จำลอง (ในการใช้งานจริงควรใช้ฐานข้อมูล)
$users = [
    [
        'id' => 1,
        'username' => 'user1',
        'password' => password_hash('password1', PASSWORD_DEFAULT),
        'name' => 'ผู้ใช้ 1',
        'email' => 'user1@example.com',
        'balance' => 5000
    ],
    [
        'id' => 2,
        'username' => 'user2',
        'password' => password_hash('password2', PASSWORD_DEFAULT),
        'name' => 'ผู้ใช้ 2',
        'email' => 'user2@example.com',
        'balance' => 10000
    ]
];

// ตรวจสอบการส่งค่าจากฟอร์มสมัครสมาชิก
$registerError = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register-submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
    $usernameExists = false;
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            $usernameExists = true;
            break;
        }
    }
    
    // ตรวจสอบว่าอีเมลซ้ำหรือไม่
    $emailExists = false;
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            $emailExists = true;
            break;
        }
    }
    
    if ($usernameExists) {
        $registerError = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่อผู้ใช้อื่น';
    } else if ($emailExists) {
        $registerError = 'อีเมลนี้ถูกใช้งานแล้ว กรุณาใช้อีเมลอื่น';
    } else if ($password !== $confirmPassword) {
        $registerError = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    } else if (strlen($password) < 8) {
        $registerError = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } else {
        // สมัครสมาชิกสำเร็จ (ในกรณีจริงควรบันทึกลงฐานข้อมูล)
        $success = true;
        
        if ($success) {
            // ตั้งค่าข้อความแจ้งเตือนสำหรับหน้าล็อกอิน
            $_SESSION['login_message'] = 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ';
            header('Location: login.php');
            exit;
        } else {
            $registerError = 'เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง';
        }
    }
}

// แสดงส่วน header
renderHeader('สมัครสมาชิก - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);
?>

<section class="page-title">
    <div class="container">
        <h1>สมัครสมาชิก</h1>
        <p>สมัครสมาชิกเพื่อใช้งานเว็บไซต์หวยออนไลน์</p>
    </div>
</section>

<div class="container">
    <?php if (!empty($registerError)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $registerError; ?>
    </div>
    <?php endif; ?>
    
    <section class="section">
        <h2 class="section-title">กรอกข้อมูลเพื่อสมัครสมาชิก</h2>
        
        <form id="register-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required>
                <div class="form-text">ชื่อผู้ใช้ต้องมีความยาว 4-20 ตัวอักษร และประกอบด้วยตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น</div>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</div>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirm-password" name="confirm-password" class="form-control" placeholder="ยืนยันรหัสผ่าน" required>
            </div>
            
            <div class="form-group">
                <label for="name">ชื่อ-นามสกุล</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="ชื่อ-นามสกุล" required>
            </div>
            
            <div class="form-group">
                <label for="email">อีเมล</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="อีเมล" required>
            </div>
            
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="เบอร์โทรศัพท์">
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="agree-terms" name="agree-terms" class="form-check-input" required>
                    <label for="agree-terms" class="form-check-label">ฉันยอมรับ <a href="#" target="_blank">ข้อตกลงและเงื่อนไข</a> การใช้งานเว็บไซต์</label>
                </div>
            </div>
            
            <button type="submit" name="register-submit" class="btn btn-accent">สมัครสมาชิก</button>
            
            <div style="margin-top: 1rem;">
                <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
            </div>
        </form>
    </section>
</div>

<style>
.form-text {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

.form-check {
    display: flex;
    align-items: flex-start;
}

.form-check-input {
    margin-top: 0.3rem;
    margin-right: 0.5rem;
}

.form-check-label {
    font-weight: normal;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    
    // ตรวจสอบชื่อผู้ใช้
    usernameInput.addEventListener('input', function() {
        const username = this.value;
        const usernameRegex = /^[a-zA-Z0-9]{4,20}$/;
        
        if (username === '') {
            this.setCustomValidity('');
        } else if (!usernameRegex.test(username)) {
            this.setCustomValidity('ชื่อผู้ใช้ต้องมีความยาว 4-20 ตัวอักษร และประกอบด้วยตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // ตรวจสอบรหัสผ่าน
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password === '') {
            this.setCustomValidity('');
        } else if (password.length < 8) {
            this.setCustomValidity('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
        } else {
            this.setCustomValidity('');
            
            // ตรวจสอบว่าตรงกับยืนยันรหัสผ่านหรือไม่
            if (confirmPasswordInput.value !== '' && confirmPasswordInput.value !== password) {
                confirmPasswordInput.setCustomValidity('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        }
    });
    
    // ตรวจสอบยืนยันรหัสผ่าน
    confirmPasswordInput.addEventListener('input', function() {
        const confirmPassword = this.value;
        const password = passwordInput.value;
        
        if (confirmPassword === '') {
            this.setCustomValidity('');
        } else if (confirmPassword !== password) {
            this.setCustomValidity('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php
// แสดงส่วน footer
renderFooter();
?>