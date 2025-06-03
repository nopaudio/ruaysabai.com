<?php
require_once 'config.php';

$pageData = getPageData(); // ดึงข้อมูลทั่วไป, examples, gallery items จาก config
$promptManager = new PromptManager(); // สร้าง instance เพื่อเรียกใช้เมธอด marketplace

$user = getCurrentUser();
$isLoggedIn = ($user !== null);

// --- คำนวณสิทธิ์การใช้งาน ---
$remaining_generations = 0;
$limit_per_period = 0;
$period_name = '';
$limit_message = '';
$can_generate = true;
$user_points = 0; // แต้มของผู้ใช้

define('GUEST_LIMIT_PER_DAY_INDEX', 3); // ลดโควต้า Guest ในหน้า index เหลือ 3

$db = Database::getInstance(); // ต้องมี $db instance

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
    } else { // Free member
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

} else { // Guest user
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

// --- ดึงข้อมูล Marketplace Prompts (ตัวอย่าง 4 รายการล่าสุด) ---
$marketplacePrompts = $promptManager->getSellablePrompts(4, 0);

// สุ่ม Prompt ยอดนิยมและ Gallery
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
                        <a href="marketplace.php"><i class="fas fa-store"></i> ตลาด Prompt</a>
                        <a href="submit_marketplace_prompt.php"><i class="fas fa-plus-circle"></i> ขาย Prompt</a>
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
        
        <main class="main-content-grid">
            <div class="main-col-left">
                <section class="form-section">
                    <h2 class="section-title">
                        <span class="section-icon"><i class="fas fa-cogs"></i></span>
                        เริ่มสร้าง Prompt ของคุณ
                    </h2>
                    
                    <div id="limit-status" class="<?php echo $can_generate ? 'limit-info' : 'limit-warning'; ?>">
                         <?php echo $limit_message; ?>
                    </div>
                    
                    <form id="promptForm">
                        <div class="form-group">
                            <label for="subject"><i class="fas fa-crosshairs"></i> หัวข้อหลัก (Subject):</label>
                            <input type="text" id="subject" name="subject" placeholder="เช่น beautiful woman, luxury car, modern house">
                        </div>
                        <div class="form-group">
                            <label for="content_type"><i class="fas fa-layer-group"></i> ประเภทเนื้อหา (Content Type):</label>
                            <select id="content_type" name="content_type">
                                <option value="">เลือกประเภท</option>
                                <option value="portrait photography">บุคคล/ตัวละคร (Portrait)</option>
                                <option value="product photography">สินค้า/ผลิตภัณฑ์ (Product)</option>
                                <option value="landscape photography">ธรรมชาติ/ทิวทัศน์ (Landscape)</option>
                                <option value="interior design">ห้อง/สถาปัตยกรรม (Interior)</option>
                                <option value="food photography">อาหาร/เครื่องดื่ม (Food)</option>
                                <option value="abstract art">ศิลปะ/นามธรรม (Abstract)</option>
                                <option value="automotive photography">ยานพาหนะ (Automotive)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="style"><i class="fas fa-palette"></i> สไตล์ภาพ (Style):</label>
                            <select id="style" name="style">
                                <option value="">เลือกสไตล์</option>
                                <option value="photorealistic">รูปถ่ายจริง (Photorealistic)</option>
                                <option value="cinematic">ภาพยนตร์ (Cinematic)</option>
                                <option value="anime style">อนิเมะ (Anime)</option>
                                <option value="oil painting">ภาพวาดสีน้ำมัน (Oil Painting)</option>
                                <option value="digital art">ดิจิทัลอาร์ต (Digital Art)</option>
                                <option value="vintage">วินเทจ (Vintage)</option>
                                <option value="minimalist">มินิมัล (Minimalist)</option>
                                <option value="cyberpunk">ไซเบอร์พังค์ (Cyberpunk)</option>
                                <option value="fantasy art">แฟนตาซี (Fantasy)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="scene"><i class="fas fa-mountain"></i> ฉากหลัง/สถานที่ (Scene):</label>
                            <input type="text" id="scene" name="scene" placeholder="เช่น beautiful garden, modern office, city street">
                        </div>
                        <div class="form-group">
                            <label for="details"><i class="fas fa-plus-circle"></i> รายละเอียดเพิ่มเติม (Details):</label>
                            <textarea id="details" name="details" placeholder="เช่น เนื้อผิว, วัสดุ, ลวดลาย, การตกแต่ง, แสงเงา"></textarea>
                        </div>
                        
                        <?php if ($can_generate): ?>
                            <button type="submit" class="generate-btn" id="generateBtn">
                                <i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt ทันที!
                            </button>
                        <?php else: ?>
                             <button type="submit" class="generate-btn" id="generateBtn" disabled>
                                <i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว
                            </button>
                        <?php endif; ?>
                    </form>
                </section>
            </div>

            <div class="main-col-right">
                 <section class="result-section">
                    <h2 class="section-title">
                        <span class="section-icon"><i class="fas fa-image"></i></span>
                        ผลลัพธ์ Prompt ของคุณ
                    </h2>
                    <div class="placeholder-message" id="placeholder-content">
                        <i class="fas fa-lightbulb"></i>
                        <h3><?php echo htmlspecialchars($pageData['settings']['placeholder_title']); ?></h3>
                        <p><?php echo htmlspecialchars($pageData['settings']['placeholder_description']); ?></p>
                    </div>
                     <div class="realtime-prompt-simulation" style="display: none;" id="realtime-simulation">
                        <p><i class="fas fa-users"></i> กำลังประมวลผล...</p>
                        <div class="typing-dots"><span></span><span></span><span></span></div>
                    </div>
                </section>
            </div>
            
            <div class="bottom-row-content">
                <section class="examples-section">
                    <div class="examples-header">
                        <div>
                            <h2><i class="fas fa-star"></i> ตัวอย่าง Prompt ยอดนิยม</h2>
                            <p>คลิกเพื่อคัดลอก Prompt ที่คุณสนใจ หรือสุ่มใหม่!</p>
                        </div>
                        <button class="refresh-btn" id="refreshExamplesBtn">
                            <i class="fas fa-sync-alt"></i> สุ่มใหม่
                        </button>
                    </div>
                    <div class="examples-grid" id="examples-grid">
                        <?php /* JavaScript will populate this */ ?>
                    </div>
                </section>

                <section class="marketplace-preview-section">
                    <div class="section-title">
                        <span class="section-icon"><i class="fas fa-store"></i></span>
                        Prompt จาก Marketplace (ล่าสุด)
                    </div>
                    <?php if (!empty($marketplacePrompts)): ?>
                        <div class="marketplace-grid">
                            <?php foreach ($marketplacePrompts as $mpPrompt): ?>
                                <div class="marketplace-card">
                                    <?php if (!empty($mpPrompt['image_url']) && filter_var($mpPrompt['image_url'], FILTER_VALIDATE_URL)): ?>
                                        <div class="marketplace-card-image">
                                            <img src="<?php echo htmlspecialchars($mpPrompt['image_url']); ?>" alt="<?php echo htmlspecialchars($mpPrompt['title']); ?>" loading="lazy" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#aaa;background-color:#f0f0f0;\'>ไม่มีรูปภาพ</div>';">
                                        </div>
                                    <?php endif; ?>
                                    <div class="marketplace-card-content">
                                        <h3 class="marketplace-card-title"><?php echo htmlspecialchars($mpPrompt['title']); ?></h3>
                                        <p class="marketplace-card-seller">โดย: <?php echo htmlspecialchars($mpPrompt['seller_username'] ?? 'ไม่ระบุ'); ?></p>
                                        <div class="marketplace-card-footer">
                                            <span class="marketplace-card-price"><i class="fas fa-coins"></i> <?php echo htmlspecialchars($mpPrompt['price_points']); ?></span>
                                            <a href="view_sellable_prompt.php?id=<?php echo $mpPrompt['id']; ?>" class="btn-view-details">ดูรายละเอียด</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="marketplace.php" class="view-all-marketplace-link"><i class="fas fa-arrow-right"></i> ดู Prompt ทั้งหมดในตลาด</a>
                    <?php else: ?>
                        <div class="placeholder-message">
                            <i class="fas fa-store-slash"></i>
                            <p>ยังไม่มี Prompt วางขายในตลาดตอนนี้</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            
            <section class="gallery-section">
                <div class="gallery-header">
                    <div>
                        <h2><i class="fas fa-images"></i> <?php echo htmlspecialchars($pageData['settings']['gallery_title']); ?></h2>
                        <p><?php echo htmlspecialchars($pageData['settings']['gallery_description']); ?></p>
                    </div>
                    <div class="gallery-controls">
                         <button class="refresh-btn" id="refreshGalleryBtn" style="margin-left: auto;"> <i class="fas fa-sync-alt"></i> สุ่มแกลเลอรี่</button>
                    </div>
                </div>
                <div class="horizontal-gallery">
                    <div class="gallery-grid" id="gallery-container">
                        <?php /* JavaScript will populate this */ ?>
                    </div>
                </div>
            </section>
        </main> 
        
        <footer class="site-footer">
            <div class="footer-links">
                <a href="index.php">หน้าหลัก</a> |
                <a href="marketplace.php">ตลาด Prompt</a> |
                <a href="profile.php">โปรไฟล์ของฉัน</a> |
                <a href="#">เกี่ยวกับเรา</a> |
                <a href="#">ติดต่อเรา</a>
            </div>
            <div class="footer-socials">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
            <p class="footer-copyright">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageData['settings']['site_title']); ?>. สงวนลิขสิทธิ์</p>
        </footer>

    </div> 
    <script>
        // --- Global Variables & Initial Data ---
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        const currentUserMemberType = '<?php echo $isLoggedIn ? $user['member_type'] : 'guest'; ?>';
        let currentRemainingGenerations = <?= is_numeric($remaining_generations) ? $remaining_generations : -1 ?>; // -1 for unlimited
        const guestLimitPerDay = <?= GUEST_LIMIT_PER_DAY_INDEX ?>;

        let allExamples = <?php echo json_encode($popularPrompts); ?>;
        let allGalleryItems = <?php echo json_encode($galleryItems); ?>;

        // --- DOM Elements ---
        const onlineCountDisplay = document.getElementById('onlineCountDisplay');
        const realtimeSimulation = document.getElementById('realtime-simulation');
        const promptFormInputs = document.querySelectorAll('#promptForm input, #promptForm select, #promptForm textarea');
        const examplesGrid = document.getElementById('examples-grid');
        const refreshExamplesBtn = document.getElementById('refreshExamplesBtn');
        const galleryContainer = document.getElementById('gallery-container');
        const refreshGalleryBtn = document.getElementById('refreshGalleryBtn');
        const generateBtn = document.getElementById('generateBtn');
        const limitStatusDiv = document.getElementById('limit-status');

        // --- Utility Functions ---
        function htmlspecialchars(str) {
            if (typeof str !== 'string' && typeof str !== 'number') return '';
            str = String(str);
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }

        // --- UI Update Functions ---
        function updateOnlineCount() {
            if (!onlineCountDisplay) return;
            let currentOnlineCount = parseInt(onlineCountDisplay.textContent);
            setInterval(() => {
                const change = Math.floor(Math.random() * 7) - 3; // -3 to +3
                currentOnlineCount = Math.max(20, currentOnlineCount + change); // Min 20
                onlineCountDisplay.textContent = currentOnlineCount;
            }, 4500);
        }

        function setupRealtimeSimulation() {
            if (!realtimeSimulation) return;
            promptFormInputs.forEach(input => {
                input.addEventListener('focus', () => { realtimeSimulation.style.display = 'block'; });
            });
        }
        
        function displayExamples() {
            if (!examplesGrid || !allExamples) return;
            shuffleArray(allExamples);
            examplesGrid.innerHTML = '';
            // Adjusted numToShow logic based on common screen breakpoints
            const numToShow = window.innerWidth < 768 ? 1 : (window.innerWidth < 1024 ? 2 : (Math.random() < 0.5 ? 2 : 3));

            if (!canGeneratePromptNow()) { 
                 examplesGrid.innerHTML = `
                    <div style="text-align: center; padding: 30px; background: rgba(251, 146, 60, 0.08); border-radius: 15px; border: 1.5px dashed rgba(251, 146, 60, 0.25); grid-column: 1 / -1;">
                        <i class="fas fa-lock" style="font-size: 2.5rem; color: #d97706; margin-bottom: 12px;"></i>
                        <h4 style="color: #d97706; margin-bottom: 8px; font-size:1.1em;">คุณใช้สิทธิ์สร้าง Prompt หมดแล้ว</h4>
                        <p style="color: #525252; margin-bottom: 15px; font-size:0.85em;">กรุณารอให้สิทธิ์รีเซ็ตในวันถัดไป/เดือนถัดไป หรือ <a href="subscribe.php" style="color: #d97706; text-decoration: underline; font-weight:bold;">อัปเกรดสมาชิก</a> เพื่อรับสิทธิ์เพิ่มเติม</p>
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
                    <div class="example-prompt" id="example-${index}">
                        ${htmlspecialchars(example.prompt)}
                    </div>
                    <button class="copy-btn" onClick="copyToClipboard('example-${index}', this)">
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
            const numToShow = Math.min(allGalleryItems.length, Math.floor(Math.random() * 3) + 4); 
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
                            <button class="generate-image-btn" onclick="openImageGenerator('${item.prompt.replace(/'/g, "\\'")}')">
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
                        <div class="gallery-prompt" id="gallery-${index}">
                            ${htmlspecialchars(item.prompt)}
                        </div>
                        <div class="gallery-actions">
                            <button class="copy-btn" onClick="copyToClipboard('gallery-${index}', this)">
                                <i class="fas fa-copy"></i> คัดลอก
                            </button>
                            <button class="use-prompt-btn" onClick="usePromptInForm('${item.prompt.replace(/'/g, "\\'")}')">
                                <i class="fas fa-edit"></i> ใช้ในฟอร์ม
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

        function copyToClipboard(elementId, buttonElement) {
            const textToCopy = document.getElementById(elementId)?.innerText;
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
            // Simple approach: split the prompt by commas and try to fill fields.
            const parts = promptText.split(',').map(p => p.trim());
            
            document.getElementById('subject').value = parts[0] || ''; // Assume first part is subject

            // Heuristics for other fields (very basic)
            const contentTypes = ["portrait photography", "product photography", "landscape photography", "interior design", "food photography", "abstract art", "automotive photography"];
            const styles = ["photorealistic", "cinematic", "anime style", "oil painting", "digital art", "vintage", "minimalist", "cyberpunk", "fantasy art"];
            
            let detailsArray = [];
            let sceneFound = '';

            parts.slice(1).forEach(part => {
                if (contentTypes.includes(part.toLowerCase()) && !document.getElementById('content_type').value) {
                    document.getElementById('content_type').value = part.toLowerCase();
                } else if (styles.some(s => part.toLowerCase().includes(s)) && !document.getElementById('style').value) {
                     const matchedStyle = styles.find(s => part.toLowerCase().includes(s));
                     if(matchedStyle) document.getElementById('style').value = matchedStyle;
                } else if (part.toLowerCase().startsWith('in ') && !sceneFound) {
                    sceneFound = part.substring(3);
                } else if (!contentTypes.includes(part.toLowerCase()) && !styles.some(s => part.toLowerCase().includes(s)) && !part.toLowerCase().startsWith('in ') &&
                           !["masterpiece", "ultra-detailed", "high resolution", "sharp focus", "professional photography", "cinematic lighting", "8k"].includes(part.toLowerCase())) {
                    detailsArray.push(part);
                }
            });
            document.getElementById('scene').value = sceneFound;
            document.getElementById('details').value = detailsArray.join(', ');

            document.getElementById('placeholder-content').style.display = 'none';
            const resultSection = document.querySelector('.result-section');
            const oldPromptOutput = resultSection.querySelector('.prompt-output');
            if(oldPromptOutput) oldPromptOutput.remove(); // Clear previous result
            
            const newPromptOutput = document.createElement('div');
            newPromptOutput.className = 'prompt-output';
            newPromptOutput.innerHTML = `
                <h3><i class="fas fa-paste" style="color: var(--primary-color);"></i> Prompt ที่เลือกใช้:</h3>
                <div class="prompt-text" id="selected-prompt-display">${htmlspecialchars(promptText)}</div>
                <button class="copy-btn" onclick="copyToClipboard('selected-prompt-display', this)">
                    <i class="fas fa-copy"></i> คัดลอก Prompt นี้
                </button>
            `;
            resultSection.appendChild(newPromptOutput);

            // Scroll to form or result
            const formSection = document.querySelector('.form-section');
            if(formSection) formSection.scrollIntoView({ behavior: 'smooth' });
            showSuccessMessage('นำ Prompt มาใส่ในฟอร์มแล้ว');
        }

        function openImageGenerator(prompt = '') {
            // Replace with your actual image generator URL or logic
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
            if (success) { // Only decrement if successful and not unlimited
                if (currentUserMemberType !== 'yearly' && currentRemainingGenerations > 0) { // Check > 0 before decrement for numeric limits
                    currentRemainingGenerations--;
                }
            }
            updateLimitStatusDisplay(); // Update display regardless of success/failure to reflect current state
            
            if (generateBtn) { // Ensure generateBtn exists
                const canGen = canGeneratePromptNow();
                generateBtn.disabled = !canGen;
                if (canGen) {
                    generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> สร้าง Prompt ทันที!';
                } else {
                     generateBtn.innerHTML = '<i class="fas fa-ban"></i> สิทธิ์การสร้างหมดแล้ว';
                }
            }
            displayExamples(); // Refresh examples, which might show a "limit reached" message if applicable
        }
        
        function updateLimitStatusDisplay() {
            if (!limitStatusDiv) return;
            let message, className;
            const periodNameTh = htmlspecialchars('<?php echo $period_name; ?>'); // วันนี้ / เดือนนี้

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

        function generatePrompt() {
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
            const baseQuality = 'masterpiece, ultra-detailed, photorealistic, high resolution, sharp focus, professional photography, cinematic lighting, 8k';
            if (subject) promptText += subject + ', ';
            if (contentType) promptText += contentType + ', ';
            if (style) promptText += style + ' style, ';
            if (scene) promptText += 'in ' + scene + ', ';
            if (details) promptText += details + ', ';
            promptText += baseQuality;
            
            if (generateBtn) {
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...';
                generateBtn.disabled = true;
            }
            
            saveUserPrompt({ subject, content_type: contentType, style, scene, details, generated_prompt: promptText })
            .then((result) => {
                if (result.success) {
                    const resultSection = document.querySelector('.result-section');
                    document.getElementById('placeholder-content').style.display = 'none'; 
                    const oldPromptOutput = resultSection.querySelector('.prompt-output');
                    if(oldPromptOutput) oldPromptOutput.remove();
                    
                    const newPromptOutput = document.createElement('div');
                    newPromptOutput.className = 'prompt-output';
                    newPromptOutput.innerHTML = `
                        <h3><i class="fas fa-check-circle" style="color: var(--success-color);"></i> Prompt ที่สร้างขึ้น:</h3>
                        <div class="prompt-text" id="generated-prompt">${htmlspecialchars(promptText)}</div>
                        <button class="copy-btn" onclick="copyToClipboard('generated-prompt', this)">
                            <i class="fas fa-copy"></i> คัดลอก Prompt
                        </button>
                    `;
                    resultSection.appendChild(newPromptOutput);
                } else {
                    updateUIAfterGenerationAttempt(false, result.message);
                }
            }).catch((error) => {
                console.error('Error in generatePrompt -> saveUserPrompt:', error);
                showAlert('error', "เกิดข้อผิดพลาดในการเชื่อมต่อ โปรดลองอีกครั้ง");
                updateUIAfterGenerationAttempt(false, "เกิดข้อผิดพลาดในการเชื่อมต่อ");
            });
        }
        
        function saveUserPrompt(data) {
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
                    showSuccessMessage(result.message || 'สร้าง Prompt สำเร็จ!');
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