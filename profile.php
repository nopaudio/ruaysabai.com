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
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=667eea&color=ffffff&size=200&rounded=true';

// ดึงสถิติการใช้งาน
$db = Database::getInstance();
$stats = [
    'total_prompts' => 0,
    'today_prompts' => 0,
    'remaining_credits' => 0,
    'this_month_prompts' => 0
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
    
    // นับจำนวน prompt เดือนนี้
    $thisMonth = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
    $stats['this_month_prompts'] = $thisMonth[0]['count'] ?? 0;
    
    // คำนวณสิทธิ์ที่เหลือ
    if ($member_type == 'monthly') {
        $limit = 60;
        $stats['remaining_credits'] = $limit - $stats['this_month_prompts'];
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

// คำนวณอายุการใช้งาน
$joinDate = new DateTime($user['created_at']);
$now = new DateTime();
$diff = $now->diff($joinDate);
$daysSinceJoin = $diff->days;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>โปรไฟล์ของฉัน | AI Prompt Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 25px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(20px);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #8A2BE2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .avatar {
            width: 120px; 
            height: 120px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .avatar:hover {
            transform: scale(1.05);
        }
        
        .avatar-ring {
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.1); opacity: 0; }
        }
        
        .profile-header h2 {
            margin: 0 0 8px 0;
            font-weight: 800;
            font-size: 2.2em;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .profile-header .username {
            font-size: 1.1em;
            opacity: 0.9;
            margin-bottom: 15px;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }
        
        .member-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .member-badge:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .member-badge.premium {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #333;
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
        }
        
        .join-date {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .profile-content {
            padding: 40px 30px;
        }
        
        .stats-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 1.4em;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-sublabel {
            color: #999;
            font-size: 12px;
            margin-top: 5px;
            font-weight: 400;
        }
        
        .subscription-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .subscription-card.warning {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.1), rgba(245, 101, 101, 0.1));
            border-color: rgba(251, 146, 60, 0.3);
        }
        
        .subscription-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #22c55e, #10b981);
        }
        
        .subscription-card.warning::before {
            background: linear-gradient(90deg, #fb923c, #f59e0b);
        }
        
        .subscription-title {
            font-weight: 700;
            font-size: 1.1em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            background: rgba(248, 250, 252, 0.8);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(71, 85, 105, 0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #374151;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }
        
        .form-control:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .form-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .action-btn.success {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.3);
        }
        
        .action-btn.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .alert {
            padding: 18px 24px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
            color: #059669;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .progress-ring {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
        }
        
        .progress-ring circle {
            fill: transparent;
            stroke-width: 6;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .progress-ring .background {
            stroke: #e5e7eb;
        }
        
        .progress-ring .progress {
            stroke: url(#gradient);
            stroke-dasharray: 188.4;
            stroke-dashoffset: 188.4;
            transition: stroke-dashoffset 0.5s ease;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .profile-container {
                margin: 0;
                border-radius: 20px;
            }
            
            .profile-header {
                padding: 30px 20px;
            }
            
            .profile-content {
                padding: 30px 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .join-date {
                position: static;
                display: inline-block;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="join-date">
                <i class="fas fa-calendar"></i> สมาชิกเมื่อ <?= $daysSinceJoin ?> วันที่แล้ว
            </div>
            
            <div class="avatar-container">
                <img src="<?= htmlspecialchars($avatar) ?>" class="avatar" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=667eea&color=ffffff&size=200&rounded=true'">
                <div class="avatar-ring"></div>
            </div>
            
            <h2><?= htmlspecialchars($user['full_name']) ?></h2>
            <div class="username">@<?= htmlspecialchars($user['username']) ?></div>
            
            <span class="member-badge <?= $user['member_type'] !== 'free' ? 'premium' : '' ?>">
                <i class="fas fa-<?= $user['member_type'] === 'free' ? 'user' : 'crown' ?>"></i>
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
            <div class="stats-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    สถิติการใช้งาน
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total_prompts'] ?></div>
                        <div class="stat-label">Prompt ทั้งหมด</div>
                        <div class="stat-sublabel">ตั้งแต่เริ่มใช้งาน</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['this_month_prompts'] ?></div>
                        <div class="stat-label">Prompt เดือนนี้</div>
                        <div class="stat-sublabel">เฉลี่ย <?= $stats['this_month_prompts'] > 0 ? round($stats['this_month_prompts'] / max(1, date('j')), 1) : 0 ?> ต่อวัน</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['today_prompts'] ?></div>
                        <div class="stat-label">Prompt วันนี้</div>
                        <div class="stat-sublabel">อัปเดตแบบ real-time</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['remaining_credits'] === 'ไม่จำกัด' ? '∞' : max(0, $stats['remaining_credits']) ?></div>
                        <div class="stat-label">สิทธิ์คงเหลือ</div>
                        <div class="stat-sublabel">
                            <?= $stats['period'] ?>
                            <?php if ($stats['limit'] !== 'ไม่จำกัด'): ?>
                                (<?= $stats['limit'] - max(0, $stats['remaining_credits']) ?>/<?= $stats['limit'] ?> ใช้แล้ว)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ข้อมูลการสมัครสมาชิก -->
            <?php if ($user['member_type'] !== 'free'): ?>
                <div class="subscription-card">
                    <div class="subscription-title">
                        <i class="fas fa-crown" style="color: #fbbf24;"></i>
                        ข้อมูลการสมัครสมาชิก
                    </div>
                    <p style="margin: 0; color: #374151; line-height: 1.6;">
                        <strong>แพ็กเกจ:</strong> <?= $memberLabels[$user['member_type']] ?><br>
                        <?php if ($user['expire_date']): ?>
                            <strong>หมดอายุ:</strong> <?= date('d/m/Y', strtotime($user['expire_date'])) ?>
                            <span style="color: #6b7280;">(อีก <?= max(0, (new DateTime($user['expire_date']))->diff(new DateTime())->days) ?> วัน)</span>
                        <?php endif; ?>
                        <br><strong>สถานะ:</strong> <span style="color: #059669;">ใช้งานได้</span>
                    </p>
                </div>
            <?php else: ?>
                <div class="subscription-card warning">
                    <div class="subscription-title">
                        <i class="fas fa-info-circle" style="color: #f59e0b;"></i>
                        อัปเกรดสมาชิก
                    </div>
                    <p style="margin: 0; color: #92400e; line-height: 1.6;">
                        สมัครสมาชิกเพื่อรับสิทธิ์พิเศษมากขึ้น! ใช้งานได้มากกว่า และได้รับฟีเจอร์เพิ่มเติม
                    </p>
                </div>
            <?php endif; ?>

            <!-- ข้อความแจ้งเตือน -->
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>

            <!-- ฟอร์มแก้ไขข้อมูล -->
            <div class="form-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    แก้ไขข้อมูลส่วนตัว
                </div>
                
                <form method="post" action="profile.php" autocomplete="on" id="profileForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" disabled>
                        <div class="form-help">Username ไม่สามารถเปลี่ยนแปลงได้</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> ชื่อ-สกุล
                        </label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="form-control" required placeholder="กรอกชื่อ-สกุลของคุณ">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required placeholder="กรอกอีเมลของคุณ">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> Avatar URL
                        </label>
                        <input type="url" name="avatar_url" value="<?= htmlspecialchars($user['avatar_url']) ?>" class="form-control" placeholder="https://example.com/avatar.jpg (ไม่บังคับ)" onChange="previewAvatar(this.value)">
                        <div class="form-help">ใส่ URL รูปภาพสำหรับ Avatar (ถ้าไม่ใส่จะใช้รูปสร้างอัตโนมัติ)</div>
                        
                        <!-- Avatar Preview -->
                        <div id="avatarPreview" style="margin-top: 15px; text-align: center; display: none;">
                            <div style="font-size: 12px; color: #6b7280; margin-bottom: 8px;">ตัวอย่าง Avatar:</div>
                            <img id="previewImage" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid #e5e7eb;" />
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-update" id="submitBtn">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>

            <!-- ปุ่มต่างๆ -->
            <div class="action-buttons">
                <a href="index.php" class="action-btn primary">
                    <i class="fas fa-home"></i> กลับหน้าหลัก
                </a>
                
                <?php if ($user['member_type'] == 'free'): ?>
                    <a href="subscribe.php?plan=monthly" class="action-btn success">
                        <i class="fas fa-crown"></i> อัปเกรดสมาชิก
                    </a>
                <?php endif; ?>
                
                <a href="logout.php" class="action-btn danger" onClick="return confirm('คุณต้องการออกจากระบบหรือไม่?')">
                    <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </div>

    <script>
        // Preview avatar function
        function previewAvatar(url) {
            const preview = document.getElementById('avatarPreview');
            const previewImage = document.getElementById('previewImage');
            
            if (url && url.trim() !== '') {
                previewImage.src = url;
                previewImage.onerror = function() {
                    preview.style.display = 'none';
                    showNotification('ไม่สามารถโหลดรูปภาพได้', 'error');
                };
                previewImage.onload = function() {
                    preview.style.display = 'block';
                };
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            `;
            
            if (type === 'success') {
                notification.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
                notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            } else if (type === 'error') {
                notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                notification.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            } else {
                notification.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Form submission with loading state
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            submitBtn.disabled = true;
            
            // Re-enable button after form submission
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }, 5000);
            });
        });
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            @keyframes slideOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
            
            /* Gradient background for stats */
            .stat-card:nth-child(1) { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); }
            .stat-card:nth-child(2) { background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1)); }
            .stat-card:nth-child(3) { background: linear-gradient(135deg, rgba(251, 146, 60, 0.1), rgba(245, 101, 101, 0.1)); }
            .stat-card:nth-child(4) { background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(219, 39, 119, 0.1)); }
        `;
        document.head.appendChild(style);
        
        // Update stats periodically (every 30 seconds)
        setInterval(function() {
            // You can add AJAX call here to update stats in real-time
            console.log('Stats update check...');
        }, 30000);
        
        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Add loading animation for avatar
        document.querySelector('.avatar').addEventListener('load', function() {
            this.style.animation = 'fadeIn 0.5s ease';
        });
        
        // Add CSS for fade in animation
        const fadeInStyle = document.createElement('style');
        fadeInStyle.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
        `;
        document.head.appendChild(fadeInStyle);
    </script>
</body>
</html>