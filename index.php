<?php
// index.php (ฉบับสมบูรณ์ FINAL - แก้ไข Gallery และ Online Count)
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

$pm = new PromptManager();
$db = Database::getInstance();

if ($isLoggedIn) {
    $user_id = $user['id'];
    $member_type = $user['member_type'];
    $user_points = (int)($user['points_balance'] ?? 0);
    
    if ($member_type == 'monthly') {
        $limit_per_period = (int)$pm->getSetting('limit_monthly', 60);
        $used_result = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $used_count = !empty($used_result) ? (int)$used_result[0]['count'] : 0;
        $remaining_generations = $limit_per_period - $used_count;
        $period_name = 'เดือนนี้';
    } elseif ($member_type == 'yearly') {
        $remaining_generations = 'ไม่จำกัด';
        $limit_per_period = 'ไม่จำกัด';
        $period_name = 'ปีนี้';
    } else { // free member
        $limit_per_period = (int)$pm->getSetting('limit_free', 10);
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
} else { // Guest
    $limit_per_period = (int)$pm->getSetting('limit_guest', 5);
    $period_name = 'วันนี้'; 
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip'; 
    $guest_used_result = $db->select( "SELECT COUNT(*) as count FROM user_prompts WHERE (user_id IS NULL OR user_id = 0) AND ip_address = ? AND DATE(created_at) = CURDATE()", [$ip_address] );
    $guest_used_count = !empty($guest_used_result) ? (int)$guest_used_result[0]['count'] : 0; 
    $remaining_generations = $limit_per_period - $guest_used_count; 
    if ($remaining_generations <= 0) { 
        $can_generate = false; 
        $limit_message = "ผู้ใช้ทั่วไป: คุณใช้สิทธิ์ครบ ".$limit_per_period." ครั้งแล้วสำหรับ{$period_name}. <a href='register.php' style='color: inherit; text-decoration: underline;'>สมัครสมาชิก</a> หรือ <a href='login.php' style='color: inherit; text-decoration: underline;'>เข้าสู่ระบบ</a>"; 
    } else {
         $limit_message = "ผู้ใช้ทั่วไป: เหลือสิทธิ์ {$remaining_generations}/".$limit_per_period." ครั้ง ({$period_name})"; 
    }
}

$latest_articles = [];
try {
    $latest_articles = $db->select("SELECT title, slug, icon FROM articles WHERE status = 'published' ORDER BY id DESC LIMIT 4");
} catch (Exception $e) {
    // ป้องกัน error ถ้าไม่มีตาราง
}

// สร้างตัวเลขออนไลน์แบบ dynamic
$base_online = 127;
$time_factor = floor(time() / 300); // เปลี่ยนทุก 5 นาที
$random_variance = ($time_factor % 37) + rand(-15, 25);
$current_online = max(50, $base_online + $random_variance);

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
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 10px; }
        .category-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 10px; border: 1px solid #d1d5db; border-radius: 8px; background-color: #f9fafb; color: #374151; font-size: 0.9em; font-weight: 500; cursor: pointer; transition: all 0.2s ease-in-out; text-align: center; }
        .category-btn:hover { border-color: #667eea; background-color: #f0f1ff; color: #4338ca; }
        .category-btn.active { background-color: #667eea; color: white; border-color: #667eea; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transform: translateY(-2px); }
        .category-btn i { font-size: 1.1em; }
        .template-list { display: flex; flex-direction: column; gap: 8px; }
        .template-item { padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s ease-in-out; background-color: #fff; }
        .template-item:hover { border-color: #a5b4fc; background-color: #f5f6ff; }
        .template-item.active { border-color: #667eea; background-color: #e0e7ff; color: #312e81; font-weight: 600; }
        .template-item p { margin-top: 4px !important; font-size: 0.85em !important; color: #4b5563 !important; }
        #promptForm { margin-top: 20px; }
        .prompt-text-content, .gallery-prompt-text { user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; }
        .gallery-prompt-text { background-color: #f8f9fa; padding: 8px 10px; border: 1px solid #e9ecef; border-radius: 6px; font-size: 0.9em; color: #495057; min-height: 50px; max-height: 70px; overflow-y: auto; word-wrap: break-word; white-space: pre-wrap; line-height: 1.5; }
        .gallery-loader-container { text-align: center; padding: 20px; grid-column: 1 / -1; }
        #load-more-btn { background-color: #667eea; color: white; border: none; padding: 12px 25px; font-size: 1em; border-radius: 8px; cursor: pointer; transition: background-color 0.3s; font-weight: 500; }
        #load-more-btn:hover { background-color: #5a67d8; }
        #load-more-btn:disabled { background-color: #a0aec0; cursor: not-allowed; }
        .loader-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Animation สำหรับ online count */
        .online-indicator { transition: all 0.3s ease; }
        .online-indicator.updating { transform: scale(1.05); color: #10b981; }
        
        /* Gallery styles */
        .gallery-section { position: relative; }
        .gallery-refresh-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10;
        }
        .gallery-refresh-indicator.show { opacity: 1; }
		
		
        
        /* Improved gallery grid - แสดงแบบตาราง ไม่เลื่อน */
 .gallery-grid {
    display: card;
    grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
    gap: 16px;
    margin-top: 15px;
}

.card {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.card img {
  object-fit: contain;
  width: 100%;
  height: auto;
  max-height: 100%; /* ถ้า container มี max-height */
}

.card-content {
    padding: 12px;
}

.card-content h4 {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: bold;
}

.card-content p {
    margin: 0;
    font-size: 14px;
    color: #555;
}
        
        .gallery-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .gallery-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .gallery-image-wrapper {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
        }
        
        .gallery-actual-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .gallery-item:hover .gallery-actual-image {
            transform: scale(1.05);
        }
        
        .gallery-content {
            padding: 12px;
        }
        
        .gallery-title {
            font-size: 0.95em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .gallery-title i {
            margin-right: 6px;
            color: #667eea;
        }
        
        .gallery-copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8em;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 8px;
        }
        
        .gallery-copy-btn:hover {
            background: #5a67d8;
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
                                <?php $memberLabels = ['monthly' => 'รายเดือน', 'yearly' => 'รายปี']; echo $memberLabels[$user['member_type']] ?? ''; ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="online-status-container">
                <div class="online-status">
                    <div class="online-indicator">
                        <div class="online-dot"></div>
                        <span id="onlineCountDisplay"><?php echo $current_online; ?></span>&nbsp;กำลังใช้งาน                    </div>
                </div>
            </div>
            <div class="header-content">
                <h1><i class="fas fa-brain"></i> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?></h1>
                <p><?php echo htmlspecialchars($pageData['settings']['site_description']); ?></p>
            </div>
        </header>
        
        <main class="card">
            <section class="gallery-section">
                <div class="gallery-header">
                    <div>
                        <h3><i class="fa-solid fa-fire"></i>Prompt ที่น่าสนใจ</h3>
                        <p>เลือกดูตัวอย่างภาพและคัดลอก Prompt ไปใช้งานได้ทันที</p>
                    </div>
                    <div class="gallery-refresh-indicator" id="galleryRefreshIndicator">
                        <i class="fas fa-sync-alt fa-spin"></i> กำลังอัปเดต...
                    </div>
                </div>
                <div class="gallery-grid" id="gallery-container"></div>
                <div class="gallery-loader-container" id="gallery-loader">
                    <button id="load-more-btn" class="load-more-btn" style="display:none;"></button>
                    <div id="loader-spinner" class="loader-spinner" style="display:none;"></div>
                </div>
            </section>

            <div class="main-left-column-stack"> 
                <section class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon"><i class="fas fa-cogs"></i></span>🎯 สร้าง Prompt มืออาชีพ
                    </h2>
                    <div id="limit-status" class="<?php echo $can_generate ? 'limit-info' : 'limit-warning'; ?>">
                         <?php echo $limit_message; ?>
                    </div>
                    <div id="new-prompt-generator-ui">
                        <div class="category-selector-container">
                            <h3 style="margin-bottom: 12px; font-size: 1em; color: #374151; font-weight: 600;"><i class="fas fa-list-ul" style="color: #667eea; margin-right: 6px;"></i>1. เลือกหมวดหมู่:</h3>
                            <div id="category-grid-container" class="category-grid"></div>
                        </div>
                        <div class="template-selector-container" style="display: none;">
                            <h3 style="margin-bottom: 12px; font-size: 1em; color: #374151; font-weight: 600;"><i class="fas fa-clipboard-list" style="color: #667eea; margin-right: 6px;"></i>2. เลือกเทมเพลต:</h3>
                            <div id="template-list-container" class="template-list"></div>
                        </div>
                        <div id="template-input-area-container" class="template-input-area" style="display: none;">
                            <h3 style="margin-bottom: 15px; font-size: 1em; color: #374151; font-weight: 600;"><i class="fas fa-edit" style="color: #667eea; margin-right: 6px;"></i>3. กรอกข้อมูล:</h3>
                            <div id="dynamic-inputs-container"></div>
                        </div>
                    </div>
                    <form id="promptForm">
                        <div id="original-form-fields"></div>
                        <?php if ($can_generate): ?>
                            <button type="submit" class="generate-btn" id="generateBtn"><i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt มืออาชีพ!</button>
                        <?php else: ?>
                             <button type="submit" class="generate-btn" id="generateBtn" disabled><i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว</button>
                        <?php endif; ?>
                    </form>
                </section> 

                <section class="articles-section">
                    <h2 class="section-title">
                        <span class="section-icon"><i class="fas fa-newspaper"></i></span>📚 คู่มือ AI & Prompt
                    </h2>
                    <div class="articles-link-list">
                        <?php if (!empty($latest_articles)): ?>
                            <?php foreach ($latest_articles as $article): ?>
                                <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="article-link-item">
                                    <i class="<?php echo htmlspecialchars($article['icon']); ?>"></i> <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                            <?php endforeach; ?>
                            <a href="articles.php" class="article-link-item" style="font-weight: bold; justify-content: center; background-color: #f7fafc;">ดูบทความทั้งหมด...</a>
                        <?php else: ?>
                            <p style="text-align:center; color:#888; font-size:0.9em; padding: 10px;">ยังไม่มีบทความในขณะนี้</p>
                        <?php endif; ?>
                    </div>
                </section> 
            </div> 

            <section class="result-section" id="result-section">
                <h2 class="section-title">
                    <span class="section-icon"><i class="fas fa-sparkles"></i></span>✨ ผลลัพธ์ Prompt ของคุณ
                </h2>
                <div class="placeholder-message" id="placeholder-content">
                    <i class="fas fa-magic"></i>
                    <h3><?php echo htmlspecialchars($pageData['settings']['placeholder_title']); ?></h3>
                    <p><?php echo htmlspecialchars($pageData['settings']['description']); ?></p>
                </div>
                 <div class="realtime-prompt-simulation" style="display: none;" id="realtime-simulation">
                    <p><i class="fas fa-cog fa-spin"></i> กำลังสร้าง Prompt มืออาชีพสำหรับคุณ...</p>
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                </div>
            </section> 
        </main> 
        
        <footer class="site-footer">
            <div class="footer-links"><a href="index.php">หน้าหลัก</a> | <a href="profile.php">โปรไฟล์</a> | <a href="articles.php">คู่มือ AI</a> | <a href="about.php">เกี่ยวกับเรา</a> | <a href="contact.php">ติดต่อ</a></div>
            <div class="footer-socials"><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a> <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a> <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a> <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a></div>
            <p class="footer-copyright">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?>. สงวนลิขสิทธิ์</p>
        </footer>
    </div> 

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    const currentUserMemberType = '<?= $isLoggedIn ? $user['member_type'] : 'guest'; ?>';
    let currentRemainingGenerations = <?= is_numeric($remaining_generations) ? $remaining_generations : 999999 ?>;
    const guestLimitPerDay = <?= $limit_per_period ?>;
    
    const onlineCountDisplay = document.getElementById('onlineCountDisplay');
    const realtimeSimulation = document.getElementById('realtime-simulation');
    const categoryGridContainer = document.getElementById('category-grid-container');
    const templateSelectorContainer = document.querySelector('.template-selector-container');
    const templateListContainer = document.getElementById('template-list-container');
    const templateInputAreaContainer = document.getElementById('template-input-area-container');
    const dynamicInputsContainer = document.getElementById('dynamic-inputs-container');
    const generateBtn = document.getElementById('generateBtn');
    const limitStatusDiv = document.getElementById('limit-status');
    const resultSectionElement = document.getElementById('result-section');
    const galleryRefreshIndicator = document.getElementById('galleryRefreshIndicator');

    let galleryCurrentPage = 1;
    let isLoadingGallery = false;
    let lastGalleryRefresh = 0;
    let displayedGalleryIds = new Set(); // เก็บ ID ที่แสดงแล้ว
    const galleryContainer = document.getElementById('gallery-container');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const loaderSpinner = document.getElementById('loader-spinner');

    // ระบบการอัปเดตจำนวนคนออนไลน์
    function updateOnlineCount() {
        const indicator = document.querySelector('.online-indicator');
        if (!indicator || !onlineCountDisplay) return;
        
        indicator.classList.add('updating');
        
        // สุ่มตัวเลขใหม่ตามเวลา
        const baseCount = 127;
        const timeFactor = Math.floor(Date.now() / 300000); // เปลี่ยนทุก 5 นาที
        const variance = (timeFactor % 37) + Math.floor(Math.random() * 40) - 15;
        const newCount = Math.max(50, baseCount + variance);
        
        // Animate การเปลี่ยนแปลง
        const currentCount = parseInt(onlineCountDisplay.textContent) || baseCount;
        const diff = newCount - currentCount;
        const steps = 20;
        const stepSize = diff / steps;
        
        let step = 0;
        const animateCount = setInterval(() => {
            step++;
            const displayCount = Math.round(currentCount + (stepSize * step));
            onlineCountDisplay.textContent = displayCount;
            
            if (step >= steps) {
                clearInterval(animateCount);
                onlineCountDisplay.textContent = newCount;
                setTimeout(() => {
                    indicator.classList.remove('updating');
                }, 500);
            }
        }, 50);
    }

    // ดึงข้อมูลจากฐานข้อมูลจริง
    async function loadGalleryItems(page = 1, forceRefresh = false) {
        if (isLoadingGallery) return;
        isLoadingGallery = true;
        
        if (page === 1) {
            galleryContainer.innerHTML = '';
            displayedGalleryIds.clear();
        }
        
        loadMoreBtn.style.display = 'none';
        loaderSpinner.style.display = 'block';

        try {
            // สร้าง URL สำหรับเรียก API
            const url = new URL('get_gallery_data.php', window.location.origin);
            url.searchParams.append('page', page);
            url.searchParams.append('per_page', 8);
            url.searchParams.append('exclude_ids', Array.from(displayedGalleryIds).join(','));
            url.searchParams.append('refresh', forceRefresh ? '1' : '0');
            url.searchParams.append('timestamp', Date.now());

            const response = await fetch(url.toString());
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    // ตรวจสอบว่าไม่ซ้ำ
                    if (displayedGalleryIds.has(item.id)) return;
                    
                    displayedGalleryIds.add(item.id);
                    
                    const galleryCard = document.createElement('div');
                    galleryCard.className = 'gallery-item';
                    galleryCard.setAttribute('data-id', item.id);
                    
                    // ใช้ข้อมูลจริงจากฐานข้อมูล
                    const promptText = escapeHtml(item.generated_prompt || item.prompt || 'ไม่มีข้อมูล Prompt');
                    const title = escapeHtml(item.subject || `Prompt #${item.id}`);
                    const imageUrl = item.image_url || getDefaultImage(item.content_type);
                    const icon = getIconByContentType(item.content_type);
                    
                    galleryCard.innerHTML = `
                        <div class="gallery-image-wrapper">
                            <img src="${imageUrl}" alt="${title}" loading="lazy" class="gallery-actual-image" 
                                 onerror="this.src='https://images.unsplash.com/photo-1455849318743-b2233052fcff?w=400&h=300&fit=crop'">
                        </div>
                        <div class="gallery-content">
                            <h4 class="gallery-title">
                                <i class="${icon}"></i>${title}
                            </h4>
                            <div class="gallery-prompt-container">
                                <div class="gallery-prompt-text" oncontextmenu="return false;">${promptText}</div>
                                <button class="gallery-copy-btn" onclick="copyGalleryPrompt('${promptText.replace(/'/g, "\\'")}', this, ${item.id})" title="คัดลอก Prompt">
                                    <i class="fas fa-copy"></i> คัดลอก
                                </button>
                            </div>
                        </div>`;
                    
                    galleryContainer.appendChild(galleryCard);
                });
                
                // แสดงปุ่มโหลดเพิ่มถ้ามีข้อมูลเพิ่ม
                if (data.has_more) {
                    loadMoreBtn.style.display = 'block';
                    galleryCurrentPage = page + 1;
                } else {
                    loadMoreBtn.style.display = 'none';
                }
                
            } else {
                if (page === 1) {
                    galleryContainer.innerHTML = '<p style="text-align:center; color:#888; grid-column: 1 / -1; padding: 40px;">ยังไม่มี Prompt ในแกลเลอรี่</p>';
                }
                loadMoreBtn.style.display = 'none';
            }
            
        } catch (error) {
            console.error('Gallery loading error:', error);
            
            if (page === 1) {
                galleryContainer.innerHTML = `
                    <p style="text-align:center; color:#e53e3e; grid-column: 1 / -1; padding: 40px;">
                        <i class="fas fa-exclamation-triangle"></i><br>
                        เกิดข้อผิดพลาดในการโหลด Gallery<br>
                        <small>กรุณาตรวจสอบไฟล์ get_gallery_data.php</small>
                    </p>`;
            }
            
        } finally {
            isLoadingGallery = false;
            loaderSpinner.style.display = 'none';
        }
    }

    // ฟังก์ชันสำหรับรีเซท Gallery
    function refreshGalleryWithAnimation() {
        if (galleryRefreshIndicator) {
            galleryRefreshIndicator.classList.add('show');
            
            setTimeout(() => {
                displayedGalleryIds.clear(); // ล้าง ID ที่แสดงแล้ว
                galleryCurrentPage = 1;
                loadGalleryItems(1, true);
                lastGalleryRefresh = Date.now();
                
                setTimeout(() => {
                    galleryRefreshIndicator.classList.remove('show');
                }, 2000);
            }, 500);
        }
    }

    // Helper functions
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getDefaultImage(contentType) {
        const imageMap = {
            'image-generation': 'https://images.unsplash.com/photo-1455849318743-b2233052fcff?w=400&h=300&fit=crop',
            'content-creation': 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=400&h=300&fit=crop',
            'writing': 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=400&h=300&fit=crop',
            'business': 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=300&fit=crop'
        };
        return imageMap[contentType] || 'https://images.unsplash.com/photo-1455849318743-b2233052fcff?w=400&h=300&fit=crop';
    }

    function getIconByContentType(contentType) {
        const iconMap = {
            'image-generation': 'fas fa-image',
            'content-creation': 'fas fa-video',
            'writing': 'fas fa-pencil-alt',
            'business': 'fas fa-chart-line'
        };
        return iconMap[contentType] || 'fas fa-magic';
    }

    // คัดลอก Prompt จาก Gallery
    async function copyGalleryPrompt(promptText, button, galleryId) {
        if (!promptText) return;
        
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            // บันทึกการคัดลอกลงฐานข้อมูล (ถ้ามีไฟล์ log_gallery_copy.php)
            try {
                await fetch('log_gallery_copy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        gallery_item_id: galleryId, 
                        prompt_text: promptText 
                    })
                });
            } catch (logError) {
                console.warn('ไม่สามารถบันทึก log ได้:', logError);
            }

            // คัดลอกไปยัง clipboard
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(promptText);
            } else {
                // Fallback สำหรับ browser เก่า
                const textArea = document.createElement('textarea');
                textArea.value = promptText;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
            
            // แสดงผลสำเร็จ
            button.innerHTML = '<i class="fas fa-check"></i> สำเร็จ';
            button.style.backgroundColor = '#10b981';
            showSuccessMessage('คัดลอก Prompt สำเร็จ!');
            
        } catch (error) {
            console.error('Copy error:', error);
            button.innerHTML = '<i class="fas fa-times"></i> ผิดพลาด';
            button.style.backgroundColor = '#e53e3e';
            showAlert('error', 'เกิดข้อผิดพลาดในการคัดลอก');
        } finally {
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.backgroundColor = '';
                button.disabled = false;
            }, 2000);
        }
    }

    // Template categories และฟังก์ชันอื่นๆ (เหมือนเดิม)
    const promptCategories = [
        {
            id: 'image-generation', name: '🎨 สร้างภาพ AI', icon: 'fas fa-image',
            templates: [
                { 
                    id: 'portrait-photo', 
                    name: 'ภาพบุคคล/โปรเทรต', 
                    description: 'สร้าง Prompt สำหรับภาพบุคคลสมจริง', 
                    inputs: [
                        { id: 'person_description', label: 'ลักษณะบุคคล:', placeholder: 'เช่น สาวเอเชีย อายุ 25 ปี ผมยาวดำ' },
                        { id: 'clothing_style', label: 'สไตล์เสื้อผ้า:', placeholder: 'เช่น ชุดไทย, สูทธุรกิจ, เสื้อผ้าสวยงาม' },
                        { id: 'background_setting', label: 'ฉากหลัง:', placeholder: 'เช่น สวนดอกไม้, ออฟฟิศ, พระราชวัง' }
                    ],
                    promptStructure: {
                        th: "ภาพถ่าย {{person_description}} ใส่ {{clothing_style}} อยู่ในฉาก {{background_setting}}",
                        en: "Professional portrait photography of {{person_description}}, wearing {{clothing_style}}, {{background_setting}}, photorealistic, high quality portrait, perfect lighting, sharp focus, detailed skin texture, DSLR photography, 85mm lens, masterpiece, ultra-detailed, 8k"
                    }
                },
                {
                    id: 'product-showcase',
                    name: 'ภาพสินค้าเพื่อการขาย',
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
                    id: 'tiktok-script',
                    name: 'สคริปต์วิดีโอ TikTok',
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
                    id: 'blog-post',
                    name: 'บทความบล็อก',
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
                    id: 'marketing-plan',
                    name: 'แผนการตลาด',
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

    function htmlspecialchars(str) {
        if (typeof str !== 'string' && typeof str !== 'number') return '';
        str = String(str);
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function updateLimitStatusDisplay() {
        if (!limitStatusDiv) return;
        let message, className;
        const periodNameTh = htmlspecialchars('<?php echo $period_name; ?>');
        if (isLoggedIn) {
            const limitNum = parseInt('<?php echo is_numeric($limit_per_period) ? $limit_per_period : 999999; ?>');
            if (currentUserMemberType === 'yearly' || limitNum >= 999999) {
                message = `<strong>สิทธิ์การใช้งาน: ไม่จำกัด</strong> (สำหรับ${periodNameTh})`;
                className = 'limit-info';
            } else if (currentRemainingGenerations <= 0) {
                message = `<strong>คุณใช้สิทธิ์หมดแล้วสำหรับ${periodNameTh}</strong>. ${currentUserMemberType==='free'?'<a href="subscribe.php" style="color: inherit; text-decoration: underline; font-weight:bold;">อัปเกรดสมาชิก</a>':''}`;
                className = 'limit-warning';
            } else {
                message = `<strong>เหลือสิทธิ์ ${currentRemainingGenerations}/${limitNum} ครั้ง (${periodNameTh})</strong>`;
                className = currentRemainingGenerations <= 3 ? 'limit-warning' : 'limit-info';
            }
        } else {
            if (currentRemainingGenerations <= 0) {
                message = `<strong>ผู้ใช้ทั่วไป:</strong> คุณใช้สิทธิ์ครบ ${guestLimitPerDay} ครั้งแล้วสำหรับ${periodNameTh}. <a href='register.php' style='color: inherit; text-decoration: underline;'>สมัครสมาชิก</a> หรือ <a href='login.php' style='color: inherit; text-decoration: underline;'>เข้าสู่ระบบ</a>`;
                className = 'limit-warning';
            } else {
                message = `<strong>ผู้ใช้ทั่วไป:</strong> เหลือสิทธิ์ ${currentRemainingGenerations}/${guestLimitPerDay} ครั้ง (${periodNameTh})`;
                className = 'limit-info';
            }
        }
        limitStatusDiv.innerHTML = `<i class="fas fa-${className==='limit-info'?'info-circle':'exclamation-triangle'}"></i> ${message}`;
        limitStatusDiv.className = className;
        const canGen = (currentUserMemberType === 'yearly' || currentRemainingGenerations > 0);
        if(generateBtn) {
            generateBtn.disabled = !canGen;
            generateBtn.innerHTML = canGen ? '<i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt มืออาชีพ!' : '<i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว';
        }
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        let iconClass = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-times-circle' : 'fa-exclamation-triangle');
        let bgColor = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#f59e0b');
        alertDiv.innerHTML = `<i class="fas ${iconClass}"></i> ${htmlspecialchars(message)}`;
        alertDiv.style.cssText = `position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: ${bgColor}; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 10001; font-size: 0.95em; display: flex; align-items: center; gap: 10px; opacity: 0; animation: slideInDownAlert 0.4s forwards;`;
        const keyframes = `@keyframes slideInDownAlert{0%{opacity:0;transform:translate(-50%,-30px)}100%{opacity:1;transform:translate(-50%,0)}} @keyframes slideOutUpAlert{0%{opacity:1;transform:translate(-50%,0)}100%{opacity:0;transform:translate(-50%,-30px)}}`;
        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = keyframes;
        document.head.appendChild(styleSheet);
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            alertDiv.style.animation = 'slideOutUpAlert 0.4s forwards';
            setTimeout(() => { alertDiv.remove(); styleSheet.remove(); }, 400);
        }, 3500);
    }

    function showSuccessMessage(message) { showAlert('success', message); }
    
    function saveUserPrompt(data) {
        return fetch('save_user_prompt.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (result.data && typeof result.data.remaining_generations !== 'undefined') {
                    let remaining = result.data.remaining_generations;
                    currentRemainingGenerations = (remaining === 'ไม่จำกัด' || remaining >= 999999) ? 999999 : Number(remaining);
                }
                showSuccessMessage(result.message || 'สร้าง Prompt สำเร็จ!');
            } else {
                showAlert('error', result.message || 'เกิดข้อผิดพลาด');
            }
            updateLimitStatusDisplay();
            return result;
        })
        .catch(error => {
            console.error('Fetch error in saveUserPrompt:', error);
            showAlert('error', 'เกิดข้อผิดพลาดในการบันทึก Prompt');
            updateLimitStatusDisplay();
            return { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
        });
    }
    
    function loadCategories() {
        categoryGridContainer.innerHTML = '';
        promptCategories.forEach(cat => {
            const button = document.createElement('button');
            button.className = 'category-btn';
            button.innerHTML = `<i class="${cat.icon}"></i> ${htmlspecialchars(cat.name)}`;
            button.onclick = () => selectCategory(cat);
            categoryGridContainer.appendChild(button);
        });
    }

    function selectCategory(category) {
        selectedCategory = category;
        selectedTemplate = null;
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        const activeBtn = Array.from(categoryGridContainer.children).find(b => b.textContent.includes(category.name));
        if (activeBtn) activeBtn.classList.add('active');
        templateListContainer.innerHTML = '';
        if (category.templates && category.templates.length > 0) {
            category.templates.forEach(tmpl => {
                const item = document.createElement('div');
                item.className = 'template-item';
                item.innerHTML = `<strong>${htmlspecialchars(tmpl.name)}</strong><p>${htmlspecialchars(tmpl.description)}</p>`;
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
        document.querySelectorAll('.template-item').forEach(i => i.classList.remove('active'));
        const activeItem = Array.from(templateListContainer.children).find(i => i.textContent.includes(template.name));
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
    }

    function generatePrompt() {
        if (!selectedTemplate) {
            showAlert('warning', 'กรุณาเลือกเทมเพลตก่อน');
            return;
        }
        let promptTh = selectedTemplate.promptStructure.th;
        let promptEn = selectedTemplate.promptStructure.en;
        let allInputsValid = true;
        const templateData = { category: selectedCategory ? selectedCategory.name : 'N/A', template: selectedTemplate.name };
        selectedTemplate.inputs.forEach(inputConf => {
            const inputElement = document.getElementById(`template-input-${inputConf.id}`);
            if (!inputElement || inputElement.value.trim() === '') allInputsValid = false;
            const value = inputElement ? inputElement.value.trim() : '';
            const placeholder = new RegExp(`{{${inputConf.id}}}`, 'g');
            promptTh = promptTh.replace(placeholder, htmlspecialchars(value));
            promptEn = promptEn.replace(placeholder, htmlspecialchars(value));
            templateData[inputConf.id] = value;
        });
        if (!allInputsValid) {
            showAlert('warning', 'กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }
        const isImagePrompt = (selectedCategory?.id === "image-generation");
        let finalPromptEn = promptEn + (isImagePrompt ? ", masterpiece, best quality, ultra-detailed" : "");
        if (generateBtn) {
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...';
            generateBtn.disabled = true;
        }
        const saveData = { subject: `${templateData.category} - ${templateData.template}`, content_type: selectedCategory ? selectedCategory.id : '', style: 'template-generated', scene: '', details: JSON.stringify(templateData), generated_prompt: finalPromptEn };
        saveUserPrompt(saveData).then(result => {
            if (result.success) {
                resultSectionElement.classList.add('active-result', 'success-animation');
                document.getElementById('placeholder-content').style.display = 'none';
                const oldPromptOutput = resultSectionElement.querySelector('.prompt-output');
                if (oldPromptOutput) oldPromptOutput.remove();
                const newPromptOutput = document.createElement('div');
                newPromptOutput.className = 'prompt-output dual-lang';
                newPromptOutput.innerHTML = `
                    <h3><i class="fas fa-check-circle" style="color: #10b981;"></i> ✨ Prompt ที่สร้างขึ้น:</h3>
                    <div class="prompt-text-block">
                        <strong><i class="fas fa-language"></i> ภาษาไทย:</strong>
                        <div class="prompt-text-content" id="prompt-th-content" oncontextmenu="return false;">${htmlspecialchars(promptTh)}</div>
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard(document.getElementById('prompt-th-content').innerText, this)"><i class="fas fa-copy"></i> คัดลอก (ไทย)</button>
                    <hr style="margin: 20px 0; border: none; border-top: 2px solid #e2e8f0;">
                    <div class="prompt-text-block">
                        <strong><i class="fas fa-globe-americas"></i> English (สำหรับ AI):</strong>
                        <div class="prompt-text-content" id="prompt-en-content" oncontextmenu="return false;">${htmlspecialchars(finalPromptEn)}</div>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                        <button class="copy-btn" onclick="copyToClipboard(document.getElementById('prompt-en-content').innerText, this)"><i class="fas fa-copy"></i> คัดลอก (อังกฤษ)</button>
                        ${isImagePrompt ? `<button class="copy-btn" onclick="openImageGenerator(document.getElementById('prompt-en-content').innerText)" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"><i class="fas fa-magic"></i> สร้างภาพด้วย AI</button>` : ''}
                    </div>
                `;
                resultSectionElement.appendChild(newPromptOutput);
                resultSectionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                resultSectionElement.classList.remove('active-result');
            }
            if (generateBtn) {
                generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt มืออาชีพ!';
                generateBtn.disabled = false;
            }
            updateLimitStatusDisplay();
        });
    }
    
    function copyToClipboard(text, button) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => { 
                button.innerHTML = '<i class="fas fa-check"></i> คัดลอกแล้ว';
                button.style.backgroundColor = '#10b981';
                showSuccessMessage('คัดลอก Prompt สำเร็จ!'); 
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-copy"></i> คัดลอก';
                    button.style.backgroundColor = '';
                }, 2000);
            });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try { document.execCommand('copy'); } catch (err) { console.error('Fallback copy failed:', err); }
            document.body.removeChild(textArea);
            button.innerHTML = '<i class="fas fa-check"></i> คัดลอกแล้ว';
            showSuccessMessage('คัดลอก Prompt สำเร็จ!');
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-copy"></i> คัดลอก';
            }, 2000);
        }
    }
    
    function openImageGenerator(prompt) {
        const encodedPrompt = encodeURIComponent(prompt);
        const urls = [ `https://leonardo.ai/ai-art-generator?prompt=${encodedPrompt}`, `https://playground.ai/?prompt=${encodedPrompt}`, `https://www.midjourney.com/` ];
        window.open(urls[0], '_blank');
    }
    
    // ตั้งเวลาการอัปเดตระบบต่างๆ
    function initializeTimers() {
        // อัปเดตจำนวนคนออนไลน์ทุก 2-4 นาที
        setInterval(updateOnlineCount, 2000 + Math.random() * 1000);
        
        // ตรวจสอบและรีเซท Gallery ทุก 8-12 นาที
        setInterval(refreshGalleryWithAnimation, 9000 );
        
        // อัปเดตครั้งแรกหลังจาก 30 วินาที
        setTimeout(updateOnlineCount, 30000);
        setTimeout(refreshGalleryWithAnimation, 60000);
    }
    
    // เริ่มต้นระบบเมื่อหน้าเว็บโหลดเสร็จ
    document.addEventListener('DOMContentLoaded', () => {
        updateLimitStatusDisplay();
        loadCategories();
        loadGalleryItems(1);
        initializeTimers();
        
        // Event listeners
        loadMoreBtn.addEventListener('click', () => { 
            loadGalleryItems(galleryCurrentPage); 
        });
        
        document.getElementById('promptForm').addEventListener('submit', (e) => { 
            e.preventDefault(); 
            generatePrompt(); 
        });
        
        console.log('🚀 ระบบ Gallery และ Online Counter เริ่มทำงานแล้ว');
    });
	    // ฟังก์ชันสุ่มแสดงการ์ด
    function shuffleCards() {
        const gallery = document.getElementById('gallery');
        const cards = Array.from(gallery.children);
        const shuffled = cards.sort(() => Math.random() - 0.5);

        // เคลียร์ gallery แล้วใส่ใหม่แบบสุ่ม
        gallery.innerHTML = '';
        shuffled.forEach(card => gallery.appendChild(card));
    }

    // เรียกใช้เมื่อโหลดหน้าเสร็จ
    window.addEventListener('DOMContentLoaded', shuffleCards);
	
	
</script>
</body>
</html>