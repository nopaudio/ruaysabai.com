<?php
// แสดงข้อผิดพลาดทั้งหมด (ใช้เฉพาะตอนพัฒนา)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่มต้น session
session_start();

// ตรวจสอบว่ามีการส่งค่าจากฟอร์มล็อกอินหรือไม่
if (isset($_POST['login'])) {
    // บันทึกข้อมูลที่ผู้ใช้กรอกไว้ในตัวแปร session เพื่อแสดงกลับในกรณีที่มีข้อผิดพลาด
    $_SESSION['temp_username'] = $_POST['username'];

    try {
        // นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
        require_once('../config.php');
        
        // ทดสอบการเชื่อมต่อฐานข้อมูล
        $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);
        
        // ตรวจสอบการเชื่อมต่อ
        if ($conn->connect_error) {
            throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
        }
        
        // กำหนด charset เป็น utf8
        $conn->set_charset($db_config['charset']);
        
        // รับค่าจากฟอร์ม
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // ตรวจสอบว่าตาราง users มีคอลัมน์ role หรือไม่
        $hasRoleColumn = false;
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($result->num_rows > 0) {
            $hasRoleColumn = true;
        }
        
        // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
        if ($hasRoleColumn) {
            // ถ้ามีคอลัมน์ role ให้ใช้คำสั่ง SQL เดิม
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        } else {
            // ถ้าไม่มีคอลัมน์ role ให้ใช้คำสั่ง SQL ที่ไม่มี role
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        }
        
        if (!$stmt) {
            throw new Exception("เกิดข้อผิดพลาดในการสร้าง prepared statement: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // ตรวจสอบรหัสผ่าน
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // ตรวจสอบสิทธิ์แอดมิน: ถ้ามีคอลัมน์ role ให้ตรวจสอบว่าเป็น admin หรือไม่
                // ถ้าไม่มีคอลัมน์ role และชื่อผู้ใช้คือ 'admin' ถือว่าเป็นแอดมิน
                if (($hasRoleColumn && $user['role'] === 'admin') || (!$hasRoleColumn && $user['username'] === 'admin')) {
                    // ตั้งค่า session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $hasRoleColumn ? $user['role'] : 'admin';
                    
                    // ล้างข้อมูลชั่วคราว
                    unset($_SESSION['temp_username']);
                    
                    // เปลี่ยนเส้นทางไปยังหน้าแดชบอร์ด
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = "คุณไม่มีสิทธิ์เข้าถึงระบบหลังบ้าน";
                }
            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ไม่พบชื่อผู้ใช้นี้";
        }
        
        // ปิดการเชื่อมต่อฐานข้อมูล
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือยัง
if (isset($_SESSION['user_id']) && (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')) {
    header('Location: dashboard.php');
    exit();
}

// ดึงชื่อผู้ใช้จาก session ที่บันทึกไว้ชั่วคราว (ถ้ามี)
$username = isset($_SESSION['temp_username']) ? $_SESSION['temp_username'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            text-align: center;
            padding: 20px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-primary {
            background-color: #007bff;
            border: none;
            width: 100%;
            padding: 10px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: 700;
            margin-top: 10px;
        }
        
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
            height: auto;
        }
        
        .alert {
            border-radius: 5px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock fa-3x"></i>
                <div class="logo-text">ระบบหลังบ้าน</div>
            </div>
            <div class="card-body">
                <form action="index.php" method="post">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" required value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-key"></i> รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary mt-3">
                        <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                    </button>
                </form>
            </div>
        </div>
        
        <div class="back-link">
            <a href="../index.php" class="text-muted">
                <i class="fas fa-arrow-left"></i> กลับไปยังหน้าหลัก
            </a>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>