<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// ตรวจสอบการล็อกอิน
if (!isUserLoggedIn()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$errorMsg = '';
$successMsg = '';

// POST: อัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $avatar_url = trim($_POST['avatar_url']);

    if (!$full_name || !$email) {
        $errorMsg = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        try {
            $db = Database::getInstance();
            $user_id = $_SESSION['user_id'];
            
            // ตรวจสอบอีเมลซ้ำ (ยกเว้นของตัวเอง)
            $check = $db->select("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if (!empty($check)) {
                $errorMsg = 'Email นี้ถูกใช้แล้ว';
            } else {
                $update = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'avatar_url' => $avatar_url,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $result = $db->update('users', $update, "id = $user_id");
                if ($result) {
                    $successMsg = 'อัปเดตโปรไฟล์สำเร็จ';
                } else {
                    $errorMsg = 'เกิดข้อผิดพลาดในการอัปเดต';
                }
            }
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $errorMsg = 'เกิดข้อผิดพลาดในระบบ';
        }
    }
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user = getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

// ถ้าไม่มี avatar หรือรูปผิด ให้ใช้รูป default
$avatar = (!empty($user['avatar_url']) && filter_var($user['avatar_url'], FILTER_VALIDATE_URL)) 
    ? $user['avatar_url'] 
    : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';

// ดึงสถิติการใช้งาน
$db = Database::getInstance();
$stats = [
    'total_prompts' => 0,
    'today_prompts' => 0,
    'remaining_credits' => 0
];

try {
    $user_id = $user['id'];
    $member_type = $user['member_type'];
    
    // นับจำนวน prompt ทั้งหมด
    $total = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ?", [$user_id]);
    $stats['total_prompts'] = $total[0]['count'] ?? 0;
    
    // นับจำนวน prompt วันนี้
    $today = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id]);
    $stats['today_prompts'] = $today[0]['count'] ?? 0;
    
    // คำนวณสิทธิ์ที่เหลือ
    if ($member_type == 'monthly') {
        $limit = 60;
        $used = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $stats['remaining_credits'] = $limit - ($used[0]['count'] ?? 0);
        $stats['period'] = 'เดือนนี้';
    } elseif ($member_type == 'yearly') {
        $limit = 120;
        $used = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $stats['remaining_credits'] = $limit - ($used[0]['count'] ?? 0);
        $stats['period'] = 'ปีนี้';
    } else {
        $limit = 10;
        $stats['remaining_credits'] = $limit - $stats['today_prompts'];
        $stats['period'] = 'วันนี้';
    }
    
    $stats['limit'] = $limit;
} catch (Exception $e) {
    error_log('Stats error: ' . $e->getMessage());
	
}

// ดึงสถิติการใช้งาน
$db = Database::getInstance();
$stats = [
    'total_prompts' => 0,
    'today_prompts' => 0,
    'remaining_credits' => 0
];

try {
    $user_id = $user['id'];
    $member_type = $user['member_type'];
    
    // นับจำนวน prompt ทั้งหมด
    $total = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ?", [$user_id]);
    $stats['total_prompts'] = $total[0]['count'] ?? 0;
    
    // นับจำนวน prompt วันนี้
    $today = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id]);
    $stats['today_prompts'] = $today[0]['count'] ?? 0;
    
    // คำนวณสิทธิ์ที่เหลือ
    if ($member_type == 'monthly') {
        $limit = 60;
        $used = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $stats['remaining_credits'] = $limit - ($used[0]['count'] ?? 0);
        $stats['period'] = 'เดือนนี้';
        $stats['limit'] = $limit;
    } elseif ($member_type == 'yearly') {
        $stats['remaining_credits'] = 'ไม่จำกัด';
        $stats['period'] = 'ปีนี้';
        $stats['limit'] = 'ไม่จำกัด';
    } else {
        $limit = 10;
        $stats['remaining_credits'] = $limit - $stats['today_prompts'];
        $stats['period'] = 'วันนี้';
        $stats['limit'] = $limit;
    }
} catch (Exception $e) {
    error_log('Stats error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>โปรไฟล์ของฉัน | AI Prompt Generator</title>
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
        .profile-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .avatar {
            width: 100px; 
            height: 100px; 
            object-fit: cover; 
            border-radius: 50%; 
            margin-bottom: 15px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .profile-header h2 {
            margin: 0;
            font-weight: 700;
        }
        .profile-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .member-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .profile-content {
            padding: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(102, 126, 234, 0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            display: block;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
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
        .btn-update {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #667eea;
            border-color: #667eea;
        }
        .subscription-info {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .subscription-warning {
            background: rgba(251, 146, 60, 0.1);
            border: 1px solid rgba(251, 146, 60, 0.3);
            color: #ea580c;
        }
        @media (max-width: 576px) {
            .profile-container { 
                margin: 20px;
                max-width: none;
            }
            .profile-content {
                padding: 20px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($avatar) ?>" class="avatar" alt="Avatar">
            <h2><?= htmlspecialchars($user['full_name']) ?></h2>
            <p>@<?= htmlspecialchars($user['username']) ?></p>
            <span class="member-badge">
                <?php
                $memberLabels = [
                    'free' => 'สมาชิกฟรี',
                    'monthly' => 'สมาชิกรายเดือน', 
                    'yearly' => 'สมาชิกรายปี'
                ];
                echo $memberLabels[$user['member_type']] ?? 'สมาชิกฟรี';
                ?>
            </span>
        </div>
        
        <div class="profile-content">
            <!-- สถิติการใช้งาน -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_prompts'] ?></span>
                    <div class="stat-label">Prompt ทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['today_prompts'] ?></span>
                    <div class="stat-label">Prompt วันนี้</div>
                </div>
                <div class="stat-card">
                     <span class="stat-number"><?= $stats['remaining_credits'] === 'ไม่จำกัด' ? '∞' : max(0, $stats['remaining_credits']) ?></span>
                    <div class="stat-label">
                        สิทธิ์คงเหลือ (<?= $stats['period'] ?>)
                        <?php if ($stats['limit'] !== 'ไม่จำกัด'): ?>
                            <br><small><?= $stats['limit'] - max(0, $stats['remaining_credits']) ?>/<?= $stats['limit'] ?> ใช้แล้ว</small>
                        <?php endif; ?>
                </div>
            </div>

            <!-- ข้อมูลการสมัครสมาชิก -->
            <?php if ($user['member_type'] !== 'free'): ?>
                <div class="subscription-info">
                    <h6><i class="fas fa-crown"></i> ข้อมูลการสมัครสมาชิก</h6>
                    <p class="mb-0">
                        <strong>แพ็กเกจ:</strong> <?= $memberLabels[$user['member_type']] ?><br>
                        <?php if ($user['expire_date']): ?>
                            <strong>หมดอายุ:</strong> <?= date('d/m/Y', strtotime($user['expire_date'])) ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="subscription-warning">
                    <h6><i class="fas fa-info-circle"></i> อัปเกรดสมาชิก</h6>
                    <p class="mb-0">สมัครสมาชิกเพื่อรับสิทธิ์พิเศษมากขึ้น!</p>
                </div>
            <?php endif; ?>

            <!-- ข้อความแจ้งเตือน -->
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
            
            
            

            <!-- ฟอร์มแก้ไขข้อมูล -->
            <form method="post" action="profile.php" autocomplete="on">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" disabled>
                    <small class="text-muted">Username ไม่สามารถเปลี่ยนแปลงได้</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> ชื่อ-สกุล
                    </label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-image"></i> Avatar URL
                    </label>
                    <input type="url" name="avatar_url" value="<?= htmlspecialchars($user['avatar_url']) ?>" class="form-control"
                        placeholder="https://example.com/avatar.jpg (ไม่บังคับ)">
                    <small class="text-muted">ใส่ URL รูปภาพสำหรับ Avatar (ถ้าไม่ใส่จะใช้รูปเริ่มต้น)</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-update">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </form>

            <!-- ปุ่มต่างๆ -->
            <div class="action-buttons">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-home"></i> กลับหน้าหลัก
                </a>
                
                <?php if ($user['member_type'] == 'free'): ?>
                    <a href="subscribe.php?plan=monthly" class="btn btn-outline-success">
                        <i class="fas fa-crown"></i> อัปเกรดสมาชิก
                    </a>
                <?php endif; ?>
                
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    
    
</body>
</html>