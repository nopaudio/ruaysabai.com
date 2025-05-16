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
        'balance' => 5000
    ],
    [
        'id' => 2,
        'username' => 'user2',
        'password' => password_hash('password2', PASSWORD_DEFAULT),
        'name' => 'ผู้ใช้ 2',
        'balance' => 10000
    ]
];

// ตรวจสอบการส่งค่าจากฟอร์มล็อกอิน
$loginError = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login-submit'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ตรวจสอบการล็อกอิน
    $authenticated = false;
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            // ล็อกอินสำเร็จ
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['balance'] = $user['balance'];
            $authenticated = true;
            break;
        }
    }
    
    if ($authenticated) {
        // Redirect ไปยังหน้าที่ต้องการหลังจากล็อกอิน
        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
        exit;
    } else {
        // ล็อกอินไม่สำเร็จ
        $loginError = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

// ดึงข้อความแจ้งเตือนจาก session (ถ้ามี)
$message = '';
if (isset($_SESSION['login_message'])) {
    $message = $_SESSION['login_message'];
    unset($_SESSION['login_message']); // ลบข้อความหลังจากแสดงแล้ว
}

// แสดงส่วน header
renderHeader('เข้าสู่ระบบ - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);


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
                
                // ตั้งค่า session ตามโครงสร้างฐานข้อมูล
                if (isset($user['role'])) {
                    $_SESSION['role'] = $user['role'];
                }
                
                if (isset($user['is_admin'])) {
                    $_SESSION['is_admin'] = $user['is_admin'];
                }
                
                // อัปเดตเวลาเข้าสู่ระบบล่าสุด
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect ตามบทบาท
                if((isset($user['role']) && $user['role'] === 'admin') || 
                   (isset($user['is_admin']) && $user['is_admin'] == 1)) {
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



<section class="page-title">
    <div class="container">
        <h1>เข้าสู่ระบบ</h1>
        <p>เข้าสู่ระบบเพื่อใช้งานเว็บไซต์หวยออนไลน์</p>
    </div>
</section>

<div class="container">
    <?php if (!empty($message)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($loginError)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $loginError; ?>
    </div>
    <?php endif; ?>
    
    <section class="section">
        <h2 class="section-title">กรอกข้อมูลเพื่อเข้าสู่ระบบ</h2>
        
        <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="ชื่อผู้ใช้" required>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
            </div>
            
            <button type="submit" name="login-submit" class="btn btn-accent">เข้าสู่ระบบ</button>
            
            <div style="margin-top: 1rem;">
                <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
            </div>
        </form>
    </section>
    
    <section class="section">
        <h2 class="section-title">ข้อมูลเข้าสู่ระบบสำหรับทดสอบ</h2>
        <p>สำหรับการทดสอบระบบ คุณสามารถใช้ข้อมูลด้านล่างนี้เพื่อเข้าสู่ระบบ:</p>
        
        <div style="margin-top: 1rem;">
            <div><strong>ชื่อผู้ใช้:</strong> user1</div>
            <div><strong>รหัสผ่าน:</strong> password1</div>
        </div>
        
        <div style="margin-top: 1rem;">
            <div><strong>ชื่อผู้ใช้:</strong> user2</div>
            <div><strong>รหัสผ่าน:</strong> password2</div>
        </div>
    </section>
</div>

<?php
// แสดงส่วน footer
renderFooter();
?>