<?php
require_once 'config.php';

$pageData = getPageData();
$user = getCurrentUser();
$isLoggedIn = ($user !== null);

// --- คำนวณสิทธิ์การใช้งาน ---
$remaining_generations = 0;
$limit_per_period = 0;
$period_name = '';
$limit_message = '';
$can_generate = true;
$user_points = 0;

define('GUEST_LIMIT_PER_DAY_INDEX', 3);
$db = Database::getInstance();

if ($isLoggedIn) {
    $user_id = $user['id'];
    $member_type = $user['member_type'];
    $user_points = (int)($user['points_balance'] ?? 0);
    
    if ($member_type == 'monthly') {
        $limit_per_period = 60;
        $used_result = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $used_count = !empty($used_result) ? (int)$used_result[0]['count'] : 0;
        $remaining_generations = $limit_per_period - $used_count;
        $period_name = 'เดือนนี้';
    } elseif ($member_type == 'yearly') {
        $remaining_generations = 'ไม่จำกัด';
        $limit_per_period = 'ไม่จำกัด';
        $period_name = 'ปีนี้';
    } else {
        $limit_per_period = 10;
        $used_result = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id]);
        $used_count = !empty($used_result) ? (int)$used_result[0]['count'] : 0;
        $remaining_generations = $limit_per_period - $used_count;
        $period_name = 'วันนี้';
    }

    if ($remaining_generations !== 'ไม่จำกัด' && $remaining_generations <= 0) {
        $can_generate = false;
        $limit_message = "คุณใช้สิทธิ์สร้าง Prompt สำหรับสมาชิกครบแล้วสำหรับ{$period_name}";
    } else {
        $limit_message = "เหลือสิทธิ์ " . ($remaining_generations === 'ไม่จำกัด' ? 'ไม่จำกัด' : $remaining_generations."/".$limit_per_period) . " ครั้ง ({$period_name})";
    }
} else {
    $limit_per_period = GUEST_LIMIT_PER_DAY_INDEX; 
    $period_name = 'วันนี้'; 
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip'; 

    $guest_used_result = $db->select( 
        "SELECT COUNT(*) as count FROM guest_prompt_usage WHERE ip_address = ? AND DATE(prompt_generated_at) = CURDATE()", 
        [$ip_address] 
    );
    $guest_used_count = !empty($guest_used_result) ? (int)$guest_used_result[0]['count'] : 0; 
    $remaining_generations = $limit_per_period - $guest_used_count; 
    
    if ($remaining_generations <= 0) { 
        $can_generate = false; 
        $limit_message = "ผู้ใช้ทั่วไป: คุณใช้สิทธิ์ครบ ".GUEST_LIMIT_PER_DAY_INDEX." ครั้งแล้วสำหรับ{$period_name}. <a href='register.php' style='color: inherit; text-decoration: underline;'>สมัครสมาชิก</a> หรือ <a href='login.php' style='color: inherit; text-decoration: underline;'>เข้าสู่ระบบ</a>"; 
    } else {
         $limit_message = "ผู้ใช้ทั่วไป: เหลือสิทธิ์ {$remaining_generations}/".GUEST_LIMIT_PER_DAY_INDEX." ครั้ง ({$period_name})"; 
    }
}

$popularPrompts = $pageData['examples']; 
$galleryItems = $pageData['gallery']; 
shuffle($popularPrompts); 
shuffle($galleryItems); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($pageData['settings']['site_title']); ?> - สร้าง Prompt ภาพคมชัด</title>
    <meta name="description" content="<?php echo htmlspecialchars($pageData['settings']['site_description']); ?>">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <meta name="theme-color" content="#6A5ACD">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* === MAIN LAYOUT === */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        /* === RESULT SECTION - บนสุดจริงๆ === */
        .result-section {
            order: -999; /* บังคับให้ขึ้นบนสุด */
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .result-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transform: rotate(45deg);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .result-section.active-result {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 3px solid #ffffff;
            box-shadow: 
                0 20px 60px rgba(102, 126, 234, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: scale(1.02);
            animation: celebrationPulse 2s infinite alternate;
        }

        .result-section.active-result::before {
            opacity: 1;
            animation: shimmer 2s infinite;
        }

        .result-section.active-result .section-title,
        .result-section.active-result .section-title i {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        @keyframes celebrationPulse {
            0% { 
                box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
                transform: scale(1.02);
            }
            100% { 
                box-shadow: 0 25px 70px rgba(118, 75, 162, 0.5);
                transform: scale(1.03);
            }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        /* === COMPACT FORM SECTION === */
        .form-section {
            background: #ffffff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 2px solid #e2e8f0;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 20px;
            color: #374151;
        }

        .section-icon {
            font-size: 1.1em;
            color: #667eea;
        }

        /* === COMPACT CATEGORY GRID === */
        .category-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
            gap: 10px; 
            margin-bottom: 15px; 
        }

        .category-btn { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid #e1e4e8; 
            border-radius: 12px; 
            padding: 12px 8px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-weight: 600; 
            color: #4a5568; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 6px;
            font-size: 0.85em;
            position: relative;
            overflow: hidden;
        }

        .category-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: all 0.5s ease;
        }

        .category-btn:hover::before,
        .category-btn.active::before {
            left: 100%;
        }

        .category-btn:hover, 
        .category-btn.active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea; 
            color: #ffffff; 
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.25); 
        }

        .category-btn i { 
            font-size: 1.6em; 
            color: #667eea;
            transition: all 0.3s ease;
        }

        .category-btn:hover i,
        .category-btn.active i { 
            color: #ffffff;
            transform: scale(1.1);
        }

        /* === COMPACT TEMPLATE SECTION === */
        .template-list { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }

        .template-item { 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e5e7eb; 
            border-radius: 10px; 
            padding: 12px 15px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            font-size: 0.9em;
            position: relative;
            overflow: hidden;
        }

        .template-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.05), transparent);
            transition: all 0.4s ease;
        }

        .template-item:hover::before {
            left: 100%;
        }

        .template-item:hover { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            transform: translateX(3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .template-item.active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea; 
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
        }

        /* === COMPACT INPUT AREA === */
        .template-input-area { 
            margin-top: 15px; 
            padding: 15px; 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px; 
            border: 2px solid #e5e7eb;
        }

        .template-input-area .form-group { 
            margin-bottom: 12px; 
        }

        .template-input-area label { 
            font-size: 0.85em; 
            font-weight: 600; 
            color: #4a5568;
            display: block;
            margin-bottom: 5px;
        }

        .template-input-area input[type="text"], 
        .template-input-area textarea {
            font-size: 0.9em; 
            padding: 10px 12px; 
            border-radius: 8px; 
            border: 2px solid #d1d5db;
            background: #ffffff;
            transition: all 0.3s ease;
            width: 100%;
        }

        .template-input-area input:focus,
        .template-input-area textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        /* === PROMPT OUTPUT === */
        .prompt-output.dual-lang .prompt-text-block { 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 2px solid #e2e8f0; 
            padding: 15px; 
            margin-bottom: 12px; 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .prompt-output.dual-lang .prompt-text-block::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;

            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .prompt-output.dual-lang .prompt-text-block strong { 
            display: block; 
            margin-bottom: 8px; 
            color: #667eea; 
            font-size: 1em; 
            font-weight: 700;
        }

        .prompt-output.dual-lang .prompt-text-content { 
            white-space: pre-wrap; 
            word-break: break-word;
            font-size: 0.9em;
            color: #374151;
            line-height: 1.6;
            background: #f8fafc;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }

        /* === BUTTONS === */
        .copy-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85em;
            transition: all 0.3s ease;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .copy-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .generate-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1em;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .generate-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }

        .generate-btn:hover::before {
            left: 100%;
        }

        .generate-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .generate-btn:disabled {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            cursor: not-allowed;
            transform: none;
        }

        /* === COMPACT EXAMPLES & GALLERY === */
        .examples-section, .gallery-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 2px solid #e2e8f0;
        }

        .examples-header, .gallery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .examples-header h2, .gallery-header h2 {
            font-size: 1.2em;
            margin: 0;
            color: #374151;
        }

        .examples-header p, .gallery-header p {
            font-size: 0.85em;
            color: #6b7280;
            margin: 5px 0 0 0;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        /* === COMPACT ARTICLES SECTION === */
        .articles-section { 
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 20px; 
            border-radius: 15px; 
            margin-top: 20px; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 2px solid #e2e8f0;
        }

        .articles-section .section-title {
            font-size: 1.2em;
            margin-bottom: 15px;
        }

        .article-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 15px; 
        }

        .article-card { 
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 2px solid #e9ecef; 
            border-radius: 12px; 
            padding: 18px; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .article-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .article-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.12);
            border-color: #667eea;
        }

        .article-card h3 { 
            font-size: 1em; 
            color: #374151; 
            margin-bottom: 10px;
            font-weight: 700;
            line-height: 1.4;
        }

        .article-card p { 
            font-size: 0.85em; 
            color: #6b7280; 
            line-height: 1.6; 
            margin-bottom: 12px; 
        }

        .article-card a.read-more-btn { 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 8px 15px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-size: 0.8em; 
            font-weight: 600; 
            transition: all 0.3s ease; 
        }

        .article-card a.read-more-btn:hover { 
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        /* === PLACEHOLDER === */
        .placeholder-message {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            border: 2px dashed #cbd5e0;
        }

        .placeholder-message i {
            font-size: 2.5em;
            color: #9ca3af;
            margin-bottom: 15px;
            display: block;
        }

        .placeholder-message h3 {
            color: #374151;
            font-size: 1.2em;
            margin-bottom: 8px;
        }

        .placeholder-message p {
            color: #6b7280;
            font-size: 0.9em;
            line-height: 1.5;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .category-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 8px;
            }
            
            .category-btn {
                padding: 10px 6px;
                font-size: 0.8em;
            }
            
            .category-btn i {
                font-size: 1.4em;
            }
            
            .article-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .result-section,
            .form-section,
            .articles-section {
                padding: 15px;
            }

            .section-title {
                font-size: 1.1em;
            }
        }

        /* Hide original form fields */
        #original-form-fields { display: none; }

        /* Animation for success state */
        .success-animation {
            animation: successBounce 0.8s ease-out;
        }

        @keyframes successBounce {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="user-menu-container">
                <div class="user-menu">
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
                    <?php else: ?>
                        <a href="profile.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['username']); ?></a>
                         <span class="user-points-display"><i class="fas fa-coins"></i> <?php echo $user_points; ?> แต้ม</span>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                        <?php if ($user['member_type'] !== 'free'): ?>
                            <span class="member-badge">
                                <?php
                                $memberLabels = ['monthly' => 'รายเดือน', 'yearly' => 'รายปี']; 
                                echo $memberLabels[$user['member_type']] ?? ''; 
                                ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="online-status-container">
                <div class="online-status">
                    <div class="online-indicator">
                        <div class="online-dot"></div>
                        <span id="onlineCountDisplay"><?php echo htmlspecialchars($pageData['settings']['online_count']); ?></span>&nbsp;กำลังใช้งาน
                    </div>
                </div>
            </div>

            <div class="header-content">
                <h1><i class="fas fa-brain"></i> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?></h1>
                <p><?php echo htmlspecialchars($pageData['settings']['site_description']); ?></p>
            </div>
        </header>
        
        <main class="main-content">
            <!-- ผลลัพธ์ Prompt - บนสุดจริงๆ ด้วย order: -999 -->
            <section class="result-section" id="result-section">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-sparkles"></i></span>
                    ✨ ผลลัพธ์ Prompt ของคุณ
                </h2>
                <div class="placeholder-message" id="placeholder-content">
                    <i class="fas fa-magic"></i>
                    <h3><?php echo htmlspecialchars($pageData['settings']['placeholder_title']); ?></h3>
                    <p><?php echo htmlspecialchars($pageData['settings']['placeholder_description']); ?></p>
                </div>
                 <div class="realtime-prompt-simulation" style="display: none;" id="realtime-simulation">
                    <p><i class="fas fa-cog fa-spin"></i> กำลังสร้าง Prompt มืออาชีพสำหรับคุณ...</p>
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </section>

            <!-- ฟอร์มสร้าง Prompt - กระชับและเรียบร้อย -->
            <section class="form-section">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-cogs"></i></span>
                    🎯 สร้าง Prompt มืออาชีพ
                </h2>
                
                <div id="limit-status" class="<?php echo $can_generate ? 'limit-info' : 'limit-warning'; ?>">
                     <?php echo $limit_message; ?>
                </div>

                <div id="new-prompt-generator-ui">
                    <div class="category-selector-container">
                        <h3 style="margin-bottom: 12px; font-size: 1em; color: #374151; font-weight: 600;">
                            <i class="fas fa-list-ul" style="color: #667eea; margin-right: 6px;"></i>
                            1. เลือกหมวดหมู่:
                        </h3>
                        <div id="category-grid-container" class="category-grid">
                            </div>
                    </div>

                    <div class="template-selector-container" style="display: none;">
                        <h3 style="margin-bottom: 12px; font-size: 1em; color: #374151; font-weight: 600;">
                            <i class="fas fa-clipboard-list" style="color: #667eea; margin-right: 6px;"></i>
                            2. เลือกเทมเพลต:
                        </h3>
                        <div id="template-list-container" class="template-list">
                            </div>
                    </div>
                    
                    <div id="template-input-area-container" class="template-input-area" style="display: none;">
                        <h3 style="margin-bottom: 15px; font-size: 1em; color: #374151; font-weight: 600;">
                            <i class="fas fa-edit" style="color: #667eea; margin-right: 6px;"></i>
                            3. กรอกข้อมูล:
                        </h3>
                        <div id="dynamic-inputs-container">
                            </div>
                    </div>
                </div>

                <form id="promptForm">
                    <div id="original-form-fields">
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-crosshairs"></i> หัวข้อหลัก:</label>
                            <input type="text" id="subject" name="subject" placeholder="เช่น beautiful woman, luxury car">
                        </div>
                        <div class="form-group">
                            <label for="content_type"><i class="fas fa-layer-group"></i> ประเภทเนื้อหา:</label>
                            <select id="content_type" name="content_type">
                                <option value="">เลือกประเภท</option>
                                <option value="portrait photography">บุคคล/ตัวละคร</option>
                                <option value="product photography">สินค้า/ผลิตภัณฑ์</option>
                                <option value="landscape photography">ธรรมชาติ/ทิวทัศน์</option>
                                <option value="interior design">ห้อง/สถาปัตยกรรม</option>
                                <option value="food photography">อาหาร/เครื่องดื่ม</option>
                                <option value="abstract art">ศิลปะ/นามธรรม</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="style"><i class="fas fa-palette"></i> สไตล์ภาพ:</label>
                            <select id="style" name="style">
                                <option value="">เลือกสไตล์</option>
                                <option value="photorealistic">รูปถ่ายจริง</option>
                                <option value="cinematic">ภาพยนตร์</option>
                                <option value="anime style">อนิเมะ</option>
                                <option value="digital art">ดิจิทัลอาร์ต</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="scene"><i class="fas fa-mountain"></i> ฉากหลัง:</label>
                            <input type="text" id="scene" name="scene" placeholder="เช่น beautiful garden, modern office">
                        </div>
                        <div class="form-group">
                            <label for="details"><i class="fas fa-plus-circle"></i> รายละเอียดเพิ่มเติม:</label>
                            <textarea id="details" name="details" placeholder="เช่น เนื้อผิว, วัสดุ, แสงเงา"></textarea>
                        </div>
                    </div>
                    <?php if ($can_generate): ?>
                        <button type="submit" class="generate-btn" id="generateBtn"> 
                            <i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt มืออาชีพ!
                        </button>
                    <?php else: ?>
                         <button type="submit" class="generate-btn" id="generateBtn" disabled> 
                            <i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว
                        </button>
                    <?php endif; ?>
                </form>
            </section>

            <!-- Examples - กระชับลง -->
            <section class="examples-section">
                <div class="examples-header">
                    <div>
                        <h2><i class="fas fa-star"></i> ตัวอย่าง Prompt ยอดนิยม</h2>
                        <p>คลิกเพื่อคัดลอก Prompt ที่คุณสนใจ</p>
                    </div>
                    <button class="refresh-btn" id="refreshExamplesBtn">
                        <i class="fas fa-sync-alt"></i> สุ่มใหม่
                    </button>
                </div>
                <div class="examples-grid" id="examples-grid">
                    <?php /* JavaScript will populate this */ ?>
                </div>
            </section>

            <!-- Gallery - กระชับลง -->
            <section class="gallery-section">
                <div class="gallery-header">
                    <div>
                        <h2><i class="fas fa-images"></i> <?php echo htmlspecialchars($pageData['settings']['gallery_title']); ?></h2>
                        <p><?php echo htmlspecialchars($pageData['settings']['gallery_description']); ?></p>
                    </div>
                    <div class="gallery-controls">
                         <button class="refresh-btn" id="refreshGalleryBtn"> 
                            <i class="fas fa-sync-alt"></i> สุ่มใหม่
                         </button>
                    </div>
                </div>
                <div class="horizontal-gallery">
                    <div class="gallery-grid" id="gallery-container">
                        <?php /* JavaScript will populate this */ ?>
                    </div>
                </div>
            </section>

            <!-- Articles - ปรับให้กระชับและเป็น SEO -->
            <section class="articles-section">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-newspaper"></i></span>
                    📚 คู่มือ AI & Prompt
                </h2>
                <div class="article-grid">
                    <div class="article-card">
                        <h3><i class="fas fa-lightbulb"></i> 5 เทคนิคเขียน Prompt ให้ AI เข้าใจ</h3>
                        <p>เรียนรู้วิธีสื่อสารกับ AI ให้ได้ภาพสวยตรงใจ พร้อมเทคนิคเลือกคำและกำหนดรายละเอียด...</p>
                        <a href="articles/prompt-writing-guide.php" class="read-more-btn">อ่านต่อ <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="article-card">
                        <h3><i class="fas fa-palette"></i> สไตล์ภาพยอดนิยมใน AI Art</h3>
                        <p>เปรียบเทียบ Photorealistic, Anime, Cinematic และสไตล์อื่นๆ เพื่อเลือกที่เหมาะกับงาน...</p>
                        <a href="articles/ai-art-styles.php" class="read-more-btn">อ่านต่อ <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="article-card">
                        <h3><i class="fas fa-magic"></i> เปรียบเทียบ AI Tools: Midjourney vs DALL-E</h3>
                        <p>รู้จักข้อดีข้อเสียของแต่ละเครื่องมือ AI สร้างภาพ เพื่อเลือกใช้ให้เหมาะกับงาน...</p>
                        <a href="articles/ai-tools-comparison.php" class="read-more-btn">อ่านต่อ <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="article-card">
                        <h3><i class="fas fa-camera-retro"></i> Negative Prompt คืออะไร? ใช้อย่างไร?</h3>
                        <p>เทคนิคการใช้ Negative Prompt เพื่อกำจัดสิ่งที่ไม่ต้องการในภาพ ให้ได้ผลลัพธ์ที่ดีขึ้น...</p>
                        <a href="articles/negative-prompt-guide.php" class="read-more-btn">อ่านต่อ <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </section>
        </main> 
        
        <footer class="site-footer">
            <div class="footer-links">
                <a href="index.php">หน้าหลัก</a> |
                <a href="profile.php">โปรไฟล์</a> |
                <a href="articles/">คู่มือ AI</a> |
                <a href="about.php">เกี่ยวกับเรา</a> |
                <a href="contact.php">ติดต่อ</a>
            </div>
            <div class="footer-socials">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
            <p class="footer-copyright">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?>. สงวนลิขสิทธิ์</p>
        </footer>
    </div> 

    <script>
        // === GLOBAL VARIABLES ===
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>; 
        const currentUserMemberType = '<?php echo $isLoggedIn ? $user['member_type'] : 'guest'; ?>'; 
        let currentRemainingGenerations = <?= is_numeric($remaining_generations) ? $remaining_generations : -1 ?>; 
        const guestLimitPerDay = <?= GUEST_LIMIT_PER_DAY_INDEX ?>; 

        let allExamples = <?php echo json_encode($popularPrompts); ?>; 
        let allGalleryItems = <?php echo json_encode($galleryItems); ?>; 

        // === DOM ELEMENTS ===
        const onlineCountDisplay = document.getElementById('onlineCountDisplay'); 
        const realtimeSimulation = document.getElementById('realtime-simulation'); 
        const categoryGridContainer = document.getElementById('category-grid-container'); 
        const templateSelectorContainer = document.querySelector('.template-selector-container'); 
        const templateListContainer = document.getElementById('template-list-container'); 
        const templateInputAreaContainer = document.getElementById('template-input-area-container'); 
        const dynamicInputsContainer = document.getElementById('dynamic-inputs-container'); 
        const examplesGrid = document.getElementById('examples-grid'); 
        const refreshExamplesBtn = document.getElementById('refreshExamplesBtn'); 
        const galleryContainer = document.getElementById('gallery-container'); 
        const refreshGalleryBtn = document.getElementById('refreshGalleryBtn'); 
        const generateBtn = document.getElementById('generateBtn'); 
        const limitStatusDiv = document.getElementById('limit-status'); 
        const resultSectionElement = document.getElementById('result-section');

        // === COMPACT CATEGORIES - เลือกเฉพาะที่จำเป็น ===
        const promptCategories = [ 
            { 
                id: 'image-generation', name: '🎨 สร้างภาพ AI', icon: 'fas fa-image', 
                templates: [ 
                    { 
                        id: 'portrait-photo', name: 'ภาพบุคคล/โปรเทรต', 
                        description: 'สร้าง Prompt สำหรับภาพบุคคลสมจริง', 
                        inputs: [ 
                            { id: 'person_description', label: 'ลักษณะบุคคล:', placeholder: 'เช่น สาวเอเชีย อายุ 25 ปี ผมยาวดำ' }, 
                            { id: 'clothing_style', label: 'สไตล์เสื้อผ้า:', placeholder: 'เช่น ชุดไทย, สูทธุรกิจ, เสื้อผ้าสวนอนันต์' }, 
                            { id: 'background_setting', label: 'ฉากหลัง:', placeholder: 'เช่น สวนดอกไม้, ออฟฟิศ, พระราชวัง' }
                        ],
                        promptStructure: { 
                            th: "ภาพถ่าย {{person_description}} ใส่ {{clothing_style}} อยู่ในฉาก {{background_setting}}", 
                            en: "Professional portrait photography of {{person_description}}, wearing {{clothing_style}}, {{background_setting}}, photorealistic, high quality portrait, perfect lighting, sharp focus, detailed skin texture, DSLR photography, 85mm lens, masterpiece, ultra-detailed, 8k" 
                        }
                    },
                    { 
                        id: 'product-showcase', name: 'ภาพสินค้าเพื่อการขาย', 
                        description: 'สร้าง Prompt สำหรับภาพสินค้าที่ขายดี', 
                        inputs: [ 
                            { id: 'product_name', label: 'ชื่อสินค้า:', placeholder: 'เช่น นาฬิกาหรู, กระเป๋าหนัง, เครื่องสำอาง' }, 
                            { id: 'product_style', label: 'สไตล์การจัดวาง:', placeholder: 'เช่น มินิมัล, หรูหรา, โมเดิร์น' }, 
                            { id: 'background_mood', label: 'โทนสีและบรรยากาศ:', placeholder: 'เช่น โทนขาวสะอาด, โทนทองหรูหรา' }
                        ],
                        promptStructure: { 
                            th: "ภาพถ่ายสินค้า {{product_name}} จัดวางสไตล์ {{product_style}} บรรยากาศ {{background_mood}}", 
                            en: "Professional product photography of {{product_name}}, {{product_style}} styling, {{background_mood}} atmosphere, commercial photography, high resolution, studio lighting, clean composition, marketing ready, masterpiece, ultra-detailed" 
                        }
                    }
                ]
            },
            { 
                id: 'content-creation', name: '🎬 คอนเทนต์', icon: 'fas fa-video', 
                templates: [ 
                    {
                        id: 'tiktok-script', name: 'สคริปต์วิดีโอ TikTok', 
                        description: 'สร้างสคริปต์วิดีโอสั้นที่ไวรัล', 
                        inputs: [ 
                            { id: 'video_topic', label: 'หัวข้อวิดีโอ:', placeholder: 'เช่น สอนทำอาหารง่ายๆ ใน 5 นาที' }, 
                            { id: 'video_style', label: 'สไตล์วิดีโอ:', placeholder: 'เช่น ตลก, ให้ความรู้, รีวิว' }, 
                            { id: 'target_duration', label: 'ความยาว (วินาที):', placeholder: 'เช่น 15, 30, 60 วินาที' }
                        ],
                        promptStructure: { 
                            th: "สร้างสคริปต์วิดีโอ TikTok หัวข้อ {{video_topic}} สไตล์ {{video_style}} ความยาว {{target_duration}}", 
                            en: "Create viral TikTok script about {{video_topic}} in {{video_style}} style, {{target_duration}} duration. Include engaging hook, valuable content, and strong call-to-action." 
                        }
                    }
                ]
            },
            {
                id: 'writing', name: '✍️ เขียน/บล็อก', icon: 'fas fa-pencil-alt', 
                templates: [ 
                    {
                        id: 'blog-post', name: 'บทความบล็อก', 
                        description: 'สร้างบทความที่เหมาะกับ SEO', 
                        inputs: [ 
                            { id: 'article_topic', label: 'หัวข้อบทความ:', placeholder: 'เช่น 10 วิธีออมเงินสำหรับคนรุ่นใหม่' }, 
                            { id: 'target_keywords', label: 'คำค้นหาเป้าหมาย:', placeholder: 'เช่น ออมเงิน, วางแผนการเงิน' }, 
                            { id: 'writing_tone', label: 'โทนการเขียน:', placeholder: 'เช่น เป็นกันเอง, เป็นทางการ, สนุกสนาน' }
                        ],
                        promptStructure: { 
                            th: "เขียนบทความบล็อกเรื่อง {{article_topic}} โทน {{writing_tone}} ใส่คำค้นหา {{target_keywords}}", 
                            en: "Write SEO-optimized blog post about {{article_topic}} in {{writing_tone}} tone, targeting keywords {{target_keywords}}. Include clear structure with headings and engaging content." 
                        }
                    }
                ]
            },
            {
                id: 'business', name: '💼 ธุรกิจ', icon: 'fas fa-chart-line', 
                templates: [ 
                    {
                        id: 'marketing-plan', name: 'แผนการตลาด', 
                        description: 'สร้างกลยุทธ์การตลาดที่ได้ผล', 
                        inputs: [ 
                            { id: 'product_type', label: 'ประเภทสินค้า:', placeholder: 'เช่น แอปมือถือ, เครื่องสำอาง, บริการ' }, 
                            { id: 'target_market', label: 'ตลาดเป้าหมาย:', placeholder: 'เช่น วัยรุ่น, คนทำงาน, แม่บ้าน' }, 
                            { id: 'budget_range', label: 'งบประมาณ:', placeholder: 'เช่น น้อยกว่า 50,000, 100,000-500,000' }
                        ],
                        promptStructure: { 
                            th: "วางแผนการตลาด {{product_type}} สำหรับ {{target_market}} งบประมาณ {{budget_range}}", 
                            en: "Create comprehensive marketing strategy for {{product_type}} targeting {{target_market}} with {{budget_range}} budget. Include timeline and marketing channels." 
                        }
                    }
                ]
            }
        ]; 

        let selectedCategory = null; 
        let selectedTemplate = null; 

        // === ENHANCED PROMPT FUNCTIONS ===
        function getAdvancedPromptModifiers() {
            const qualityModifiers = [
                "masterpiece", "ultra-detailed", "high resolution", "8k", "professional quality",
                "award-winning", "photorealistic", "extremely detailed", "sharp focus"
            ];
            
            const lightingModifiers = [
                "cinematic lighting", "dramatic lighting", "Golden Hour lighting",
                "studio lighting", "perfect lighting", "volumetric lighting"
            ];
            
            const cameraModifiers = [
                "shot on Sony A7IV", "85mm lens", "DSLR photography",
                "professional photography", "depth of field"
            ];

            return {
                quality: shuffleArray([...qualityModifiers]).slice(0, 2),
                lighting: shuffleArray([...lightingModifiers]).slice(0, 1),
                camera: shuffleArray([...cameraModifiers]).slice(0, 1)
            };
        }

        function generateEnhancedPrompt(basePrompt, isImagePrompt = false) {
            if (!isImagePrompt) {
                return basePrompt;
            }

            const modifiers = getAdvancedPromptModifiers();
            const negativeParts = ["blurry", "low quality", "pixelated", "distorted", "watermark"];

            let enhancedPrompt = basePrompt;
            
            if (!enhancedPrompt.includes('masterpiece')) {
                enhancedPrompt += ', ' + modifiers.quality.join(', ');
            }
            
            if (!enhancedPrompt.toLowerCase().includes('lighting')) {
                enhancedPrompt += ', ' + modifiers.lighting.join(', ');
            }
            
            if (enhancedPrompt.toLowerCase().includes('photo') || enhancedPrompt.toLowerCase().includes('portrait')) {
                enhancedPrompt += ', ' + modifiers.camera.join(', ');
            }
            
            enhancedPrompt += ' | Negative: ' + shuffleArray([...negativeParts]).slice(0, 3).join(', ');

            return enhancedPrompt;
        }

        function isImageGenerationPrompt(categoryId, templateId) {
            return categoryId === 'image-generation' || 
                   templateId?.includes('photo') || 
                   templateId?.includes('image');
        }

        // === UTILITY FUNCTIONS ===
        function htmlspecialchars(str) { 
            if (typeof str !== 'string' && typeof str !== 'number') return ''; 
            str = String(str); 
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; 
            return str.replace(/[&<>"']/g, function(m) { return map[m]; }); 
        }

        function shuffleArray(array) { 
            const newArray = [...array];
            for (let i = newArray.length - 1; i > 0; i--) { 
                const j = Math.floor(Math.random() * (i + 1)); 
                [newArray[i], newArray[j]] = [newArray[j], newArray[i]]; 
            }
            return newArray;
        }

        // === UI FUNCTIONS ===
        function updateOnlineCount() { 
            if (!onlineCountDisplay) return; 
            let currentOnlineCount = parseInt(onlineCountDisplay.textContent); 
            setInterval(() => { 
                const change = Math.floor(Math.random() * 7) - 3;
                currentOnlineCount = Math.max(20, currentOnlineCount + change);
                onlineCountDisplay.textContent = currentOnlineCount; 
            }, 4500); 
        }

        function setupRealtimeSimulation() { 
            if (!realtimeSimulation) return; 
            document.querySelectorAll('#new-prompt-generator-ui input, #new-prompt-generator-ui textarea').forEach(input => { 
                input.addEventListener('focus', () => { 
                    realtimeSimulation.style.display = 'block'; 
                    setTimeout(() => {
                        realtimeSimulation.style.display = 'none';
                    }, 3000);
                }); 
            });
        }
        
        function displayExamples() { 
            if (!examplesGrid || !allExamples) return; 
            shuffleArray(allExamples); 
            examplesGrid.innerHTML = ''; 
            const numToShow = window.innerWidth < 768 ? 1 : 2; 

            if (!canGeneratePromptNow()) {  
                 examplesGrid.innerHTML = `
                    <div style="text-align: center; padding: 20px; background: rgba(251, 146, 60, 0.08); border-radius: 12px; border: 1.5px dashed rgba(251, 146, 60, 0.25);">
                        <i class="fas fa-lock" style="font-size: 2rem; color: #d97706; margin-bottom: 10px;"></i>
                        <h4 style="color: #d97706; margin-bottom: 5px;">สิทธิ์การใช้งานหมดแล้ว</h4>
                        <p style="color: #525252; font-size:0.8em;"><a href="subscribe.php" style="color: #d97706; text-decoration: underline;">อัปเกรดสมาชิก</a> เพื่อรับสิทธิ์เพิ่ม</p>
                    </div>
                `; 
                return; 
            }

            const examplesToShow = allExamples.slice(0, numToShow); 
            examplesToShow.forEach((example, index) => { 
                const card = document.createElement('div'); 
                card.className = 'example-card'; 
                card.innerHTML = `
                    <div class="example-title">
                        <i class="${htmlspecialchars(example.icon || 'fas fa-lightbulb')}"></i> 
                        ${htmlspecialchars(example.title)}
                    </div>
                    <div class="example-prompt" id="example-prompt-text-${index}">${htmlspecialchars(example.prompt)}</div>
                    <button class="copy-btn" onClick="copyToClipboard(document.getElementById('example-prompt-text-${index}').innerText, this)">
                        <i class="fas fa-copy"></i> คัดลอก
                    </button>
                `; 
                examplesGrid.appendChild(card); 
            });
        }

        function displayGalleryItems() { 
            if (!galleryContainer || !allGalleryItems) return; 
            shuffleArray(allGalleryItems); 
            galleryContainer.innerHTML = ''; 
            const numToShow = Math.min(allGalleryItems.length, 4);  
            const galleryToShow = allGalleryItems.slice(0, numToShow); 

            galleryToShow.forEach((item, index) => { 
                const galleryCard = document.createElement('div'); 
                galleryCard.className = 'gallery-item'; 
                galleryCard.innerHTML = `
                    <div class="gallery-image">
                        <img src="${htmlspecialchars(item.image_url)}" 
                             alt="${htmlspecialchars(item.title)}"
                             loading="lazy"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;background-color:#eee;color:#aaa;font-size:0.8em;border-radius:inherit;\\'>No Image</div>';">
                        <div class="gallery-overlay">
                            <button class="generate-image-btn" onclick="openImageGenerator(document.getElementById('gallery-prompt-text-${index}').innerText)">
                                <i class="fas fa-magic"></i> สร้างภาพ
                            </button>
                        </div>
                    </div>
                    <div class="gallery-content">
                        <h3 class="gallery-title">
                            <i class="${htmlspecialchars(item.icon || 'fas fa-image')}"></i>
                            ${htmlspecialchars(item.title)}
                        </h3>
                        <p class="gallery-description">${htmlspecialchars(item.description || '')}</p>
                        <div class="gallery-prompt" id="gallery-prompt-text-${index}">${htmlspecialchars(item.prompt)}</div>
                        <div class="gallery-actions">
                            <button class="copy-btn" onClick="copyToClipboard(document.getElementById('gallery-prompt-text-${index}').innerText, this)">
                                <i class="fas fa-copy"></i> คัดลอก
                            </button>
                        </div>
                    </div>
                `; 
                galleryContainer.appendChild(galleryCard); 
            });
        }
        
        function showLimitModal(message, showRegisterLink = false) { 
            let fullMessage = message; 
            if (showRegisterLink) { 
                fullMessage += `<br><br><a href='register.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>สมัครสมาชิก</a> หรือ <a href='login.php' style='color: #667eea; text-decoration: underline; font-weight: bold;'>เข้าสู่ระบบ</a> เพื่อรับสิทธิ์เพิ่มเติม`; 
            }
            const existingModal = document.querySelector('.modal-overlay'); 
            if (existingModal) existingModal.remove(); 

            const modal = document.createElement('div');
            modal.style.cssText = ` position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.75); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); `; 
            const modalContent = document.createElement('div'); 
            modalContent.style.cssText = ` background: white; padding: 35px; border-radius: 20px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); transform: scale(0.95); opacity: 0; animation: modalPopIn 0.3s forwards; `; 
            modalContent.innerHTML = ` <div style="color: #ea580c; font-size: 3.5em; margin-bottom: 20px;"><i class="fas fa-exclamation-triangle"></i></div> <h3 style="color: #1e293b; margin-bottom: 15px; font-size: 1.4em; font-weight: 700;">ขีดจำกัดการใช้งาน</h3> <p style="color: #4b5563; line-height: 1.6; margin-bottom: 30px; font-size: 0.95em;">${fullMessage}</p> <button onclick="this.closest('.modal-overlay').remove()" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 14px 35px; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 1em; transition: all 0.3s ease;">เข้าใจแล้ว</button> `; 
            const styleSheet = document.createElement("style"); styleSheet.type = "text/css"; styleSheet.innerText = "@keyframes modalPopIn { to { transform: scale(1); opacity: 1; } }"; document.head.appendChild(styleSheet); 
            modal.className = 'modal-overlay'; modal.appendChild(modalContent); document.body.appendChild(modal); 
            modal.addEventListener('click', function(e) { if (e.target === modal) { modal.remove(); } }); 
        }
        
        function showSuccessMessage(message) { 
            const alertDiv = document.createElement('div'); 
            alertDiv.className = 'custom-alert success'; // Add 'success' class 
            alertDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${htmlspecialchars(message)}`; 
            alertDiv.style.cssText = `position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: var(--success-color, #10b981); color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 10001; font-size: 0.95em; display: flex; align-items: center; gap: 10px; opacity:0; animation: slideInDownAlert 0.4s forwards;`; 
            
            const keyframes = `@keyframes slideInDownAlert { 0% { opacity: 0; transform: translate(-50%, -30px); } 100% { opacity: 1; transform: translate(-50%, 0); } } @keyframes slideOutUpAlert { 0% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; transform: translate(-50%, -30px); } }`; 
            const styleSheet = document.createElement("style"); styleSheet.type = "text/css"; styleSheet.innerText = keyframes; document.head.appendChild(styleSheet); 

            document.body.appendChild(alertDiv); 
            setTimeout(() => { 
                alertDiv.style.animation = 'slideOutUpAlert 0.4s forwards'; 
                setTimeout(() => { alertDiv.remove(); styleSheet.remove(); }, 400); 
            }, 3500); 
        }

        function showAlert(type, message) { // General alert for errors etc. 
            const alertDiv = document.createElement('div'); 
            let iconClass = 'fas fa-info-circle'; 
            let bgColor = 'var(--primary-color, #1E90FF)'; 
            if (type === 'error') { iconClass = 'fas fa-times-circle'; bgColor = 'var(--danger-color, #ef4444)'; } 
            else if (type === 'warning') { iconClass = 'fas fa-exclamation-triangle'; bgColor = 'var(--warning-color, #f59e0b)'; } 

            alertDiv.innerHTML = `<i class="${iconClass}"></i> ${htmlspecialchars(message)}`; 
            alertDiv.style.cssText = `position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: ${bgColor}; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 10001; font-size: 0.95em; display: flex; align-items: center; gap: 10px; opacity:0; animation: slideInDownAlert 0.4s forwards;`; 
             const keyframes = `@keyframes slideInDownAlert { 0% { opacity: 0; transform: translate(-50%, -30px); } 100% { opacity: 1; transform: translate(-50%, 0); } } @keyframes slideOutUpAlert { 0% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; transform: translate(-50%, -30px); } }`; 
            const styleSheet = document.createElement("style"); styleSheet.type = "text/css"; styleSheet.innerText = keyframes; document.head.appendChild(styleSheet); 

            document.body.appendChild(alertDiv); 
            setTimeout(() => { 
                alertDiv.style.animation = 'slideOutUpAlert 0.4s forwards'; 
                setTimeout(() => { alertDiv.remove(); styleSheet.remove();}, 400); 
            }, 3500); 
        }
        
        function copyToClipboard(textToCopy, buttonElement) { // isDirectText is no longer needed, always pass text
            if (!textToCopy) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => showCopySuccess(buttonElement))
                    .catch(err => {
                        console.warn('Clipboard API failed, using fallback:', err);
                        fallbackCopyToClipboard(textToCopy, buttonElement);
                    });
            } else {
                fallbackCopyToClipboard(textToCopy, buttonElement);
            }
        }

        function fallbackCopyToClipboard(text, buttonElement) { 
            const textArea = document.createElement('textarea'); 
            textArea.value = text; 
            textArea.style.position = 'fixed';  
            textArea.style.left = '-9999px'; 
            document.body.appendChild(textArea); 
            textArea.focus(); 
            textArea.select(); 
            try {
                document.execCommand('copy'); 
                showCopySuccess(buttonElement); 
            } catch (err) {
                console.error('Fallback copy failed:', err); 
                showAlert('error', 'ไม่สามารถคัดลอกได้'); 
            }
            document.body.removeChild(textArea); 
        }

        function showCopySuccess(buttonElement) { 
            if (!buttonElement) return; 
            const originalText = buttonElement.innerHTML; 
            buttonElement.innerHTML = '<i class="fas fa-check"></i> คัดลอกแล้ว!'; 
            buttonElement.style.backgroundColor = 'var(--success-color, #10b981)'; 
            setTimeout(() => { 
                buttonElement.innerHTML = originalText; 
                buttonElement.style.backgroundColor = '';  
            }, 2000); 
        }
        
        function usePromptInForm(promptText) { 
            if (!canGeneratePromptNow()) { 
                showLimitModal(isLoggedIn ? `คุณใช้สิทธิ์สร้าง Prompt สำหรับสมาชิกครบแล้วสำหรับ${htmlspecialchars('<?php echo $period_name; ?>')}.` : `คุณใช้สิทธิ์สร้าง Prompt สำหรับผู้ใช้ทั่วไปครบ ${guestLimitPerDay} ครั้งแล้วสำหรับวันนี้.`, !isLoggedIn); 
                return; 
            }
            
            if (navigator.clipboard && navigator.clipboard.writeText) { 
                navigator.clipboard.writeText(promptText) 
                    .then(() => showSuccessMessage('คัดลอก Prompt แล้ว! กรุณานำไปวางในช่องที่ต้องการของเทมเพลต')) 
                    .catch(err => fallbackCopyToClipboard(promptText, null)); 
            } else {
                fallbackCopyToClipboard(promptText, null); 
            }
            showAlert('info', 'Prompt ถูกคัดลอกแล้ว กรุณาเลือกหมวดหมู่/เทมเพลต และวางในช่องที่ต้องการ'); 
        }

        function openImageGenerator(prompt = '') { 
            const generatorUrl = `https://www.bing.com/images/create?q=${encodeURIComponent(prompt)}`; 
            window.open(generatorUrl, '_blank'); 
        }

        // --- Core Logic: Prompt Generation & Usage Update ---
        function canGeneratePromptNow() { 
            if (isLoggedIn) { 
                return currentUserMemberType === 'yearly' || currentRemainingGenerations > 0; 
            } else { // Guest 
                return currentRemainingGenerations > 0; 
            }
        }

        function updateUIAfterGenerationAttempt(success, messageFromServer) { 
            if (success) { 
                if (currentUserMemberType !== 'yearly' && currentRemainingGenerations > 0) { 
                    currentRemainingGenerations--; 
                }
            }
            updateLimitStatusDisplay(); 
            
            if (generateBtn) { 
                const canGen = canGeneratePromptNow(); 
                generateBtn.disabled = !canGen; 
                if (canGen) { 
                    generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt มืออาชีพทันที!'; 
                } else {
                     generateBtn.innerHTML = '<i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว'; 
                }
            }
            displayExamples(); 
        }
        
        function updateLimitStatusDisplay() { 
            if (!limitStatusDiv) return; 
            let message, className; 
            const periodNameTh = htmlspecialchars('<?php echo $period_name; ?>'); 

            if (isLoggedIn) { 
                if (currentUserMemberType === 'yearly') { 
                    message = `<strong>สิทธิ์การใช้งาน: ไม่จำกัด</strong> (สำหรับ${periodNameTh})`; 
                    className = 'limit-info'; 
                } else if (currentRemainingGenerations <= 0) { 
                    message = `<strong>คุณใช้สิทธิ์หมดแล้วสำหรับ${periodNameTh}</strong>. ${currentUserMemberType === 'free' ? '<a href="subscribe.php" style="color: inherit; text-decoration: underline; font-weight:bold;">อัปเกรดสมาชิก</a>' : ''}`; 
                    className = 'limit-warning'; 
                } else {
                     const limitNum = parseInt(htmlspecialchars('<?php echo $limit_per_period; ?>')); 
                    message = `<strong>เหลือสิทธิ์ ${currentRemainingGenerations}/${limitNum} ครั้ง (${periodNameTh})</strong>`; 
                    className = currentRemainingGenerations <= 3 ? 'limit-warning' : 'limit-info'; 
                }
            } else { // Guest 
                 if (currentRemainingGenerations <= 0) { 
                    message = `<strong>ผู้ใช้ทั่วไป:</strong> คุณใช้สิทธิ์ครบ ${guestLimitPerDay} ครั้งแล้วสำหรับ${periodNameTh}. <a href='register.php' style='color: inherit; text-decoration: underline;'>สมัครสมาชิก</a> หรือ <a href='login.php' style='color: inherit; text-decoration: underline;'>เข้าสู่ระบบ</a>`; 
                    className = 'limit-warning'; 
                } else {
                    message = `<strong>ผู้ใช้ทั่วไป:</strong> เหลือสิทธิ์ ${currentRemainingGenerations}/${guestLimitPerDay} ครั้ง (${periodNameTh})`; 
                    className = 'limit-info'; 
                }
            }
            limitStatusDiv.innerHTML = `<i class="fas fa-${className === 'limit-info' ? 'info-circle' : 'exclamation-triangle'}"></i> ${message}`; 
            limitStatusDiv.className = className; 
        }

        function loadCategories() { 
            categoryGridContainer.innerHTML = ''; 
            promptCategories.forEach(cat => { 
                const btn = document.createElement('button'); 
                btn.className = 'category-btn'; 
                btn.innerHTML = `<i class="${cat.icon}"></i> ${htmlspecialchars(cat.name)}`; 
                btn.onclick = () => selectCategory(cat); 
                categoryGridContainer.appendChild(btn); 
            });
        }

        function selectCategory(category) { 
            selectedCategory = category; 
            selectedTemplate = null; // Reset template selection 

            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active')); 
            const activeBtn = Array.from(categoryGridContainer.children).find(btn => btn.textContent.includes(category.name)); 
            if (activeBtn) activeBtn.classList.add('active'); 

            templateListContainer.innerHTML = ''; 
            if (category.templates && category.templates.length > 0) { 
                category.templates.forEach(tmpl => { 
                    const item = document.createElement('div'); 
                    item.className = 'template-item'; 
                    item.innerHTML = `<strong>${htmlspecialchars(tmpl.name)}</strong><p style="font-size:0.8em; color:#555; margin-top:3px;">${htmlspecialchars(tmpl.description)}</p>`; 
                    item.onclick = () => selectTemplate(tmpl); 
                    templateListContainer.appendChild(item); 
                });
                templateSelectorContainer.style.display = 'block'; 
                templateInputAreaContainer.style.display = 'none'; 
            } else {
                templateListContainer.innerHTML = '<p>ไม่พบเทมเพลตในหมวดหมู่นี้</p>'; 
                templateSelectorContainer.style.display = 'block'; 
                templateInputAreaContainer.style.display = 'none'; 
            }
            dynamicInputsContainer.innerHTML = ''; 
        }

        function selectTemplate(template) { 
            selectedTemplate = template; 

            document.querySelectorAll('.template-item').forEach(item => item.classList.remove('active')); 
            const activeItem = Array.from(templateListContainer.children).find(item => item.textContent.includes(template.name)); 
            if (activeItem) activeItem.classList.add('active'); 

            dynamicInputsContainer.innerHTML = ''; 
            if (template.inputs && template.inputs.length > 0) { 
                template.inputs.forEach(inputConf => { 
                    const formGroup = document.createElement('div'); 
                    formGroup.className = 'form-group'; 
                    const label = document.createElement('label'); 
                    label.htmlFor = `template-input-${inputConf.id}`; 
                    label.textContent = inputConf.label; 
                    
                    const inputElement = document.createElement(inputConf.type === 'textarea' ? 'textarea' : 'input'); 
                    if (inputConf.type !== 'textarea') inputElement.type = 'text'; 
                    inputElement.id = `template-input-${inputConf.id}`; 
                    inputElement.name = inputConf.id; 
                    inputElement.placeholder = inputConf.placeholder || ''; 
                    inputElement.required = true; 

                    formGroup.appendChild(label); 
                    formGroup.appendChild(inputElement); 
                    dynamicInputsContainer.appendChild(formGroup); 
                });
                templateInputAreaContainer.style.display = 'block'; 
            } else {
                templateInputAreaContainer.style.display = 'none'; 
            }
            setupRealtimeSimulation(); 
        }
        
        function generatePromptFromTemplate() { 
            if (!canGeneratePromptNow()) { 
                const msg = isLoggedIn ? 
                    `คุณใช้สิทธิ์สร้าง Prompt สำหรับสมาชิกครบแล้วสำหรับ${htmlspecialchars('<?php echo $period_name; ?>')}.` :
                    `คุณใช้สิทธิ์สร้าง Prompt สำหรับผู้ใช้ทั่วไปครบ ${guestLimitPerDay} ครั้งแล้วสำหรับวันนี้.`;
                showLimitModal(msg, !isLoggedIn);
                return;
            }

            if (!selectedTemplate) { 
                showAlert('warning', 'กรุณาเลือกเทมเพลตก่อน'); 
                return; 
            }

            let promptTh = selectedTemplate.promptStructure.th; 
            let promptEn = selectedTemplate.promptStructure.en; 
            let allInputsValid = true; 
            const templateData = { 
                category: selectedCategory ? selectedCategory.name : 'N/A', 
                template: selectedTemplate.name 
            };

            if (selectedTemplate.inputs && selectedTemplate.inputs.length > 0) { 
                selectedTemplate.inputs.forEach(inputConf => { 
                    const inputElement = document.getElementById(`template-input-${inputConf.id}`); 
                    if (inputElement && inputElement.value.trim() === '') { 
                        allInputsValid = false; 
                    }
                    const value = inputElement ? inputElement.value.trim() : ''; 
                    
                    const placeholderRegex = new RegExp(`{{${inputConf.id}}}`, 'g'); 
                    promptTh = promptTh.replace(placeholderRegex, htmlspecialchars(value)); 
                    promptEn = promptEn.replace(placeholderRegex, htmlspecialchars(value)); 
                    templateData[inputConf.id] = value; 
                });
            }

            if (!allInputsValid) { 
                showAlert('warning', 'กรุณากรอกข้อมูลในช่องที่กำหนดให้ครบถ้วน'); 
                return; 
            }
            
            // ปรับปรุง Prompt ให้มีคุณภาพมากขึ้น
            const isImagePrompt = isImageGenerationPrompt(selectedCategory?.id, selectedTemplate?.id);
            let finalPromptEn = generateEnhancedPrompt(promptEn, isImagePrompt);
            
            if (generateBtn) { 
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้าง Prompt มืออาชีพ...'; 
                generateBtn.disabled = true; 
            }

            const saveData = { 
                subject: `${templateData.category} - ${templateData.template}`, 
                content_type: selectedCategory ? selectedCategory.id : '', 
                style: 'template-generated', 
                scene: '', 
                details: JSON.stringify(templateData), 
                generated_prompt_th: promptTh, 
                generated_prompt_en: finalPromptEn, 
                generated_prompt: finalPromptEn 
            };
            
            saveUserPrompt(saveData) 
            .then((result) => { 
                if (result.success) { 
                    if (resultSectionElement) {
                        resultSectionElement.classList.add('active-result');
                        resultSectionElement.classList.add('success-animation');
                    }
                    document.getElementById('placeholder-content').style.display = 'none';  
                    const oldPromptOutput = resultSectionElement.querySelector('.prompt-output'); 
                    if(oldPromptOutput) oldPromptOutput.remove(); 
                    
                    const newPromptOutput = document.createElement('div'); 
                    newPromptOutput.className = 'prompt-output dual-lang'; 
                    newPromptOutput.innerHTML = `
                        <h3><i class="fas fa-check-circle" style="color: #10b981;"></i> ✨ Prompt มืออาชีพที่สร้างขึ้น:</h3>
                        <div class="prompt-text-block" id="generated-prompt-th-block">
                            <strong><i class="fas fa-language"></i> ภาษาไทย (สำหรับคนไทย):</strong>
                            <div class="prompt-text-content" id="prompt-th-content">${htmlspecialchars(promptTh)}</div>
                        </div>
                        <button class="copy-btn" onclick="copyToClipboard(document.getElementById('prompt-th-content').innerText, this)">
                            <i class="fas fa-copy"></i> คัดลอก (ไทย)
                        </button>
                        <hr style="margin: 20px 0; border: none; border-top: 2px solid #e2e8f0;">
                        <div class="prompt-text-block" id="generated-prompt-en-block">
                            <strong><i class="fas fa-globe-americas"></i> English (สำหรับ AI Image Generator):</strong>
                            <div class="prompt-text-content" id="prompt-en-content">${htmlspecialchars(finalPromptEn)}</div>
                        </div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                            <button class="copy-btn" onclick="copyToClipboard(document.getElementById('prompt-en-content').innerText, this)">
                                <i class="fas fa-copy"></i> คัดลอก (อังกฤษ)
                            </button>
                            ${isImagePrompt ? `<button class="copy-btn" onclick="openImageGenerator(document.getElementById('prompt-en-content').innerText)" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-magic"></i> สร้างภาพเลย
                            </button>` : ''}
                        </div>
                    `; 
                    resultSectionElement.appendChild(newPromptOutput); 
                    
                    // Scroll to result
                    resultSectionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    if (resultSectionElement) resultSectionElement.classList.remove('active-result'); 
                }
            }).catch((error) => { 
                console.error('Error in generatePromptFromTemplate -> saveUserPrompt:', error); 
                showAlert('error', "เกิดข้อผิดพลาดในการเชื่อมต่อ โปรดลองอีกครั้ง"); 
                updateUIAfterGenerationAttempt(false, "เกิดข้อผิดพลาดในการเชื่อมต่อ"); 
                if (resultSectionElement) resultSectionElement.classList.remove('active-result'); 
            });
        }

        function generatePrompt() { 
            if (selectedTemplate) { 
                generatePromptFromTemplate(); 
            } else {
                 if (!canGeneratePromptNow()) { 
                    const msg = isLoggedIn ?  
                        `คุณใช้สิทธิ์สร้าง Prompt สำหรับสมาชิกครบแล้วสำหรับ${htmlspecialchars('<?php echo $period_name; ?>')}.` : 
                        `คุณใช้สิทธิ์สร้าง Prompt สำหรับผู้ใช้ทั่วไปครบ ${guestLimitPerDay} ครั้งแล้วสำหรับวันนี้.`; 
                    showLimitModal(msg, !isLoggedIn); 
                    return; 
                }

                const subject = document.getElementById('subject').value.trim(); 
                const contentType = document.getElementById('content_type').value; 
                const style = document.getElementById('style').value; 
                const scene = document.getElementById('scene').value.trim(); 
                const details = document.getElementById('details').value.trim(); 
                
                if (!subject && !contentType && !style && !scene && !details) { 
                    showAlert('warning', 'กรุณากรอกข้อมูลอย่างน้อย 1 ช่อง'); 
                    return; 
                }
                
                let promptText = ''; 
                if (subject) promptText += subject + ', '; 
                if (contentType) promptText += contentType + ', '; 
                if (style) promptText += style + ' style, '; 
                if (scene) promptText += 'in ' + scene + ', '; 
                if (details) promptText += details + ', '; 
                
                // ปรับปรุง Prompt ให้เป็นมืออาชีพ
                const enhancedPrompt = generateEnhancedPrompt(promptText, true);
                
                if (generateBtn) { 
                    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...'; 
                    generateBtn.disabled = true; 
                }
                
                saveUserPrompt({ 
                    subject, 
                    content_type: contentType, 
                    style, 
                    scene, 
                    details, 
                    generated_prompt: enhancedPrompt,
                    generated_prompt_th: promptText,
                    generated_prompt_en: enhancedPrompt
                }) 
                .then((result) => { 
                    if (result.success) { 
                        if (resultSectionElement) {
                            resultSectionElement.classList.add('active-result');
                            resultSectionElement.classList.add('success-animation');
                        }
                        document.getElementById('placeholder-content').style.display = 'none';  
                        const oldPromptOutput = resultSectionElement.querySelector('.prompt-output'); 
                        if(oldPromptOutput) oldPromptOutput.remove(); 
                        
                        const newPromptOutput = document.createElement('div'); 
                        newPromptOutput.className = 'prompt-output'; 
                        newPromptOutput.innerHTML = `
                            <h3><i class="fas fa-check-circle" style="color: #10b981;"></i> ✨ Prompt มืออาชีพที่สร้างขึ้น:</h3>
                            <div class="prompt-text-block">
                                <div class="prompt-text-content" id="generated-prompt-single">${htmlspecialchars(enhancedPrompt)}</div>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                                <button class="copy-btn" onclick="copyToClipboard(document.getElementById('generated-prompt-single').innerText, this)">
                                    <i class="fas fa-copy"></i> คัดลอก Prompt
                                </button>
                                <button class="copy-btn" onclick="openImageGenerator(document.getElementById('generated-prompt-single').innerText)" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="fas fa-magic"></i> สร้างภาพเลย
                                </button>
                            </div>
                        `; 
                        resultSectionElement.appendChild(newPromptOutput); 
                        
                        // Scroll to result
                        resultSectionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                         if (resultSectionElement) resultSectionElement.classList.remove('active-result'); 
                    }
                }).catch((error) => { 
                    console.error('Error in generatePrompt -> saveUserPrompt:', error); 
                    showAlert('error', "เกิดข้อผิดพลาดในการเชื่อมต่อ โปรดลองอีกครั้ง"); 
                    updateUIAfterGenerationAttempt(false, "เกิดข้อผิดพลาดในการเชื่อมต่อ"); 
                    if (resultSectionElement) resultSectionElement.classList.remove('active-result'); 
                });
            }
        }
        
        function saveUserPrompt(data) { 
            if (!data.generated_prompt_th && data.generated_prompt) { 
                data.generated_prompt_th = data.generated_prompt; 
            }
            if (!data.generated_prompt_en && data.generated_prompt) { 
                data.generated_prompt_en = data.generated_prompt; 
            }

            return fetch('save_user_prompt.php', {  
                method: 'POST', 
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, 
                body: JSON.stringify(data) 
            })
            .then(response => { 
                if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); } 
                return response.json(); 
            })
            .then(result => { 
                updateUIAfterGenerationAttempt(result.success, result.message); 
                if (!result.success) { 
                    showLimitModal(result.message, result.show_auth_links === true);  
                } else {
                    showSuccessMessage(result.message || 'สร้าง Prompt มืออาชีพสำเร็จ! 🎉'); 
                }
                return result;  
            })
            .catch(error => { 
                console.error('Fetch error in saveUserPrompt:', error); 
                showAlert('error', 'เกิดข้อผิดพลาดในการบันทึก Prompt: ' + error.message); 
                updateUIAfterGenerationAttempt(false, 'เกิดข้อผิดพลาดในการบันทึก'); 
                return { success: false, message: 'เกิดข้อผิดพลาดในการบันทึก: ' + error.message }; 
            });
        }
        
        // --- Event Listeners & Initial Load ---
        document.addEventListener('DOMContentLoaded', () => { 
            updateOnlineCount(); 
            loadCategories(); 
            setupRealtimeSimulation(); 
            if (refreshExamplesBtn) refreshExamplesBtn.addEventListener('click', displayExamples); 
            if (refreshGalleryBtn) refreshGalleryBtn.addEventListener('click', displayGalleryItems); 
            
            displayExamples();  
            displayGalleryItems(); 
            window.addEventListener('resize', displayExamples);  

            updateLimitStatusDisplay(); 

            const promptForm = document.getElementById('promptForm'); 
            if (promptForm) { 
                 promptForm.addEventListener('submit', (e) => { e.preventDefault(); generatePrompt(); }); 
            }

            document.addEventListener('keydown', (e) => { if (e.ctrlKey && e.shiftKey && e.key === 'A') { e.preventDefault(); adminAccess(); } }); 
            
            document.addEventListener('touchstart', () => {}, {passive: true}); 
            document.addEventListener('touchmove', () => {}, {passive: true}); 
        });

        let autoScrollInterval = null; 
        if (galleryContainer) { 
             autoScrollInterval = setInterval(() => { 
                if (!galleryContainer) return; 
                const scrollWidth = galleryContainer.scrollWidth; 
                const clientWidth = galleryContainer.clientWidth; 
                if (scrollWidth <= clientWidth) return; 

                if (galleryContainer.scrollLeft + clientWidth >= scrollWidth - 50) { 
                    galleryContainer.scrollTo({ left: 0, behavior: 'smooth' }); 
                } else {
                    galleryContainer.scrollBy({ left: Math.min(320, clientWidth), behavior: 'smooth' }); 
                }
            }, 15000); 

            galleryContainer.addEventListener('mouseenter', () => clearInterval(autoScrollInterval)); 
            galleryContainer.addEventListener('mouseleave', () => { 
                if (!galleryContainer) return; 
                 autoScrollInterval = setInterval(() => { 
                    if (!galleryContainer) return; 
                    const scrollWidth = galleryContainer.scrollWidth; 
                    const clientWidth = galleryContainer.clientWidth; 
                    if (scrollWidth <= clientWidth) return; 

                    if (galleryContainer.scrollLeft + clientWidth >= scrollWidth - 50) { 
                        galleryContainer.scrollTo({ left: 0, behavior: 'smooth' }); 
                    } else {
                         galleryContainer.scrollBy({ left: Math.min(320, clientWidth), behavior: 'smooth' }); 
                    }
                }, 15000); 
            });
        }
        
        function adminAccess() {  
            const pass = prompt("Admin Access Code:"); 
            if (pass === "SUPER_ADMIN_2025X") { // Replace with a secure way to check password 
                window.location.href = "admin.php"; // Redirect to admin page 
            } else if (pass) { 
                alert("Incorrect Access Code."); 
            }
        }

    </script>
</body>
</html>