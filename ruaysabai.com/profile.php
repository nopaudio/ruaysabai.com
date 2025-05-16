<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// เริ่มต้น session
session_start();

// รวมไฟล์การตั้งค่า
require_once 'config.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isLoggedIn()) {
    // ถ้าไม่ได้ล็อกอิน ให้เปลี่ยนเส้นทางไปยังหน้าล็อกอิน
    header("Location: login.php");
    exit();
}

// เชื่อมต่อกับฐานข้อมูล
$db = connectDB();
if (!$db) {
    die("ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้");
}

// รับค่า user_id จาก session
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// ตรวจสอบการส่งแบบฟอร์ม
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตรวจสอบว่าเป็นการอัปเดตโปรไฟล์หรือเปลี่ยนรหัสผ่าน
    if (isset($_POST['update_profile'])) {
        // อัปเดตข้อมูลโปรไฟล์
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $fullname = trim($_POST['fullname']);
        
        // ตรวจสอบว่าอีเมลไม่ซ้ำกับผู้ใช้คนอื่น
        $check_email_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email_stmt->execute([$email, $user_id]);
        
        if ($check_email_stmt->rowCount() > 0) {
            $error_message = "อีเมลนี้มีผู้ใช้งานแล้ว กรุณาใช้อีเมลอื่น";
        } else {
            // ตรวจสอบว่าชื่อผู้ใช้ไม่ซ้ำกับผู้ใช้คนอื่น
            $check_username_stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_username_stmt->execute([$username, $user_id]);
            
            if ($check_username_stmt->rowCount() > 0) {
                $error_message = "ชื่อผู้ใช้นี้มีผู้ใช้งานแล้ว กรุณาใช้ชื่อผู้ใช้อื่น";
            } else {
                // อัปเดตข้อมูลในฐานข้อมูล
                $update_stmt = $db->prepare("UPDATE users SET username = ?, email = ?, fullname = ? WHERE id = ?");
                
                if ($update_stmt->execute([$username, $email, $fullname, $user_id])) {
                    $success_message = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
                    
                    // อัปเดตข้อมูลผู้ใช้ในตัวแปร
                    $user['username'] = $username;
                    $user['email'] = $email;
                    $user['fullname'] = $fullname;
                } else {
                    $error_message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // เปลี่ยนรหัสผ่าน
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // ตรวจสอบรหัสผ่านปัจจุบันว่าถูกต้องหรือไม่
        if (verifyPassword($current_password, $user['password'])) {
            // ตรวจสอบว่ารหัสผ่านใหม่และยืนยันรหัสผ่านตรงกัน
            if ($new_password === $confirm_password) {
                // ตรวจสอบความยาวรหัสผ่าน
                if (strlen($new_password) >= 8) {
                    // เข้ารหัสรหัสผ่านใหม่
                    $hashed_password = hashPassword($new_password);
                    
                    // อัปเดตรหัสผ่านในฐานข้อมูล
                    $update_pwd_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    
                    if ($update_pwd_stmt->execute([$hashed_password, $user_id])) {
                        $success_message = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน";
                    }
                } else {
                    $error_message = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
                }
            } else {
                $error_message = "รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน";
            }
        } else {
            $error_message = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
    } elseif (isset($_POST['update_settings'])) {
        // อัปเดตการตั้งค่าผู้ใช้
        $theme = $_POST['theme'];
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $language = $_POST['language'];
        
        // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
        $check_settings_stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $check_settings_stmt->execute([$user_id]);
        
        if ($check_settings_stmt->rowCount() > 0) {
            // อัปเดตการตั้งค่าที่มีอยู่
            $update_settings_stmt = $db->prepare("UPDATE user_settings SET theme = ?, notifications = ?, language = ? WHERE user_id = ?");
            
            if ($update_settings_stmt->execute([$theme, $notifications, $language, $user_id])) {
                $success_message = "อัปเดตการตั้งค่าเรียบร้อยแล้ว";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการอัปเดตการตั้งค่า";
            }
        } else {
            // สร้างการตั้งค่าใหม่
            $insert_settings_stmt = $db->prepare("INSERT INTO user_settings (user_id, theme, notifications, language) VALUES (?, ?, ?, ?)");
            
            if ($insert_settings_stmt->execute([$user_id, $theme, $notifications, $language])) {
                $success_message = "บันทึกการตั้งค่าเรียบร้อยแล้ว";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกการตั้งค่า";
            }
        }
    } elseif (isset($_POST['upload_avatar'])) {
        // อัปโหลดรูปโปรไฟล์
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['avatar']['type'], $allowed_types)) {
                if ($_FILES['avatar']['size'] <= $max_size) {
                    $upload_dir = 'uploads/avatars/';
                    
                    // สร้างโฟลเดอร์ถ้ายังไม่มี
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // สร้างชื่อไฟล์ใหม่
                    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                        // อัปเดตชื่อไฟล์ในฐานข้อมูล
                        $avatar_stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                        
                        if ($avatar_stmt->execute([$new_filename, $user_id])) {
                            $success_message = "อัปโหลดรูปโปรไฟล์เรียบร้อยแล้ว";
                            $user['avatar'] = $new_filename;
                        } else {
                            $error_message = "เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล";
                        }
                    } else {
                        $error_message = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
                    }
                } else {
                    $error_message = "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 2MB)";
                }
            } else {
                $error_message = "อนุญาตเฉพาะไฟล์ภาพ (JPEG, PNG, GIF) เท่านั้น";
            }
        } else {
            $error_message = "กรุณาเลือกไฟล์";
        }
    }
}

// ดึงการตั้งค่าของผู้ใช้
$settings_stmt = $db->prepare("SELECT * FROM user_settings WHERE user_id = ?");
$settings_stmt->execute([$user_id]);
$user_settings = $settings_stmt->fetch();

// ถ้าไม่มีการตั้งค่า ให้กำหนดค่าเริ่มต้น
if (!$user_settings) {
    $user_settings = [
        'theme' => 'light',
        'notifications' => 1,
        'language' => 'th'
    ];
}

// รวมไฟล์ส่วนหัวเว็บไซต์
include('includes/header.php');
include('includes/navbar.php');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>เมนูโปรไฟล์</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile-section" class="list-group-item list-group-item-action">ข้อมูลส่วนตัว</a>
                    <a href="#password-section" class="list-group-item list-group-item-action">เปลี่ยนรหัสผ่าน</a>
                    <a href="#avatar-section" class="list-group-item list-group-item-action">รูปโปรไฟล์</a>
                    <a href="#settings-section" class="list-group-item list-group-item-action">ตั้งค่าระบบ</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <?php showAlert($success_message, 'success'); ?>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <?php showAlert($error_message, 'danger'); ?>
            <?php endif; ?>
            
            <!-- แสดงข้อความแจ้งเตือนจาก session -->
            <?php showSessionAlert('message', 'success'); ?>
            <?php showSessionAlert('error', 'danger'); ?> ?>
            <?php endif; ?>
            
            <!-- ส่วนข้อมูลส่วนตัว -->
            <div class="card mb-4" id="profile-section">
                <div class="card-header">
                    <h5>ข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">บันทึกข้อมูล</button>
                    </form>
                </div>
            </div>
            
            <!-- ส่วนเปลี่ยนรหัสผ่าน -->
            <div class="card mb-4" id="password-section">
                <div class="card-header">
                    <h5>เปลี่ยนรหัสผ่าน</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                    </form>
                </div>
            </div>
            
            <!-- ส่วนรูปโปรไฟล์ -->
            <div class="card mb-4" id="avatar-section">
                <div class="card-header">
                    <h5>รูปโปรไฟล์</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" class="img-thumbnail rounded-circle" alt="Avatar" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="assets/img/default-avatar.png" class="img-thumbnail rounded-circle" alt="Default Avatar" style="width: 150px; height: 150px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="avatar" class="form-label">เลือกรูปโปรไฟล์ใหม่</label>
                                    <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*" required>
                                    <div class="form-text">อนุญาตเฉพาะไฟล์ภาพ (JPEG, PNG, GIF) ขนาดไม่เกิน 2MB</div>
                                </div>
                                <button type="submit" name="upload_avatar" class="btn btn-primary">อัปโหลดรูป</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ส่วนตั้งค่าระบบ -->
            <div class="card mb-4" id="settings-section">
                <div class="card-header">
                    <h5>ตั้งค่าระบบ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="theme" class="form-label">ธีม</label>
                            <select class="form-select" id="theme" name="theme">
                                <option value="light" <?php echo ($user_settings['theme'] === 'light') ? 'selected' : ''; ?>>สว่าง</option>
                                <option value="dark" <?php echo ($user_settings['theme'] === 'dark') ? 'selected' : ''; ?>>มืด</option>
                                <option value="auto" <?php echo ($user_settings['theme'] === 'auto') ? 'selected' : ''; ?>>อัตโนมัติ (ตามระบบ)</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notifications" name="notifications" <?php echo ($user_settings['notifications'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications">เปิดใช้งานการแจ้งเตือน</label>
                        </div>
                        <div class="mb-3">
                            <label for="language" class="form-label">ภาษา</label>
                            <select class="form-select" id="language" name="language">
                                <option value="th" <?php echo ($user_settings['language'] === 'th') ? 'selected' : ''; ?>>ไทย</option>
                                <option value="en" <?php echo ($user_settings['language'] === 'en') ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>
                        <button type="submit" name="update_settings" class="btn btn-primary">บันทึกการตั้งค่า</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เพิ่ม JavaScript สำหรับตรวจสอบความตรงกันของรหัสผ่าน -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordForm = document.querySelector('#password-section form');
    
    passwordForm.addEventListener('submit', function(event) {
        if (newPassword.value !== confirmPassword.value) {
            event.preventDefault();
            alert('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
        }
    });
    
    // ทำให้แท็บทำงานด้วย JavaScript
    const menuLinks = document.querySelectorAll('.list-group-item');
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            window.scrollTo({
                top: targetElement.offsetTop - 20,
                behavior: 'smooth'
            });
        });
    });
});
</script>

<?php
// รวมไฟล์ส่วนท้ายเว็บไซต์
include('includes/footer.php');
?>