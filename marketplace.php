<?php
require_once 'config.php';

$promptManager = new PromptManager();
$pageDataGlobal = getPageData(); // ดึงข้อมูล global สำหรับ header/footer
$currentUser = getCurrentUser();
$isLoggedIn = ($currentUser !== null);
$user_points_display = 0;
if ($isLoggedIn) {
    $user_points_display = (int)($currentUser['points_balance'] ?? 0);
}

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 12; // จำนวนรายการต่อหน้า (อาจจะปรับตามความเหมาะสม)
$offset = ($page - 1) * $limit;

// ดึงข้อมูล Prompt ที่อนุมัติแล้ว พร้อม pagination
$sellablePrompts = $promptManager->getSellablePrompts($limit, $offset);
$totalPrompts = $promptManager->getTotalApprovedSellablePrompts();
$totalPages = ceil($totalPrompts / $limit);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ตลาด Prompt - <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <style>
        /* CSS เพิ่มเติมหรือ overrides สำหรับ marketplace.php โดยเฉพาะ */
        /* (ใช้ CSS จาก style.css เป็นหลัก แต่สามารถเพิ่ม/แก้ตรงนี้ได้ถ้าต้องการ) */
        
        /* Header and User Menu - Ensure they match index.php styles */
        /* If not already in style.css, you might need to copy relevant parts here */
        /* For simplicity, we assume style.css covers these. */

        .marketplace-page-container {
            max-width: 1600px; /* ให้กว้างขึ้นสำหรับแสดงหลายรายการ */
            margin: 0 auto;
            padding: 0 15px 30px 15px; /* เพิ่ม padding ด้านล่าง */
        }

        .marketplace-header { /* (เหมือนใน index.php) */
            background: linear-gradient(135deg, var(--primary-color, #1E90FF) 0%, var(--secondary-color, #4169E1) 100%);
            color: white;
            padding: 30px 20px; /* ลด padding บนล่างเล็กน้อย */
            text-align: center;
            margin-bottom: 25px; /* ลด margin bottom */
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .marketplace-header h1 {
            font-size: clamp(1.8rem, 4vw, 2.8rem); /* ปรับขนาดให้พอดี */
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }
        .marketplace-header p {
            font-size: clamp(0.9rem, 2vw, 1.1rem); /* ปรับขนาดให้พอดี */
            opacity: 0.9;
            margin-bottom: 0;
        }

        .prompts-grid { /* (เหมือนใน index.php) */
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* ปรับ minmax เล็กน้อย */
            gap: 20px; /* ปรับ gap */
            /* padding: 0 20px; (เอาออกถ้า marketplace-page-container จัดการแล้ว) */
            /* max-width: 1400px; (เอาออกถ้า marketplace-page-container จัดการแล้ว) */
            /* margin: 0 auto 30px auto; (เอาออกถ้า marketplace-page-container จัดการแล้ว) */
        }
        .prompt-card { /* (เหมือนใน index.php) */
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .prompt-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        .prompt-card-image {
            width: 100%;
            height: 180px; /* ปรับความสูงรูปภาพ */
            background-color: #e9ecef;
            overflow: hidden;
        }
        .prompt-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .prompt-card:hover .prompt-card-image img {
            transform: scale(1.05);
        }
        .prompt-card-content {
            padding: 15px; /* ปรับ padding */
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .prompt-card-title {
            font-size: 1.1rem; /* ปรับขนาด */
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.3;
            /* For text overflow */
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Limit to 2 lines */
            -webkit-box-orient: vertical;
            min-height: 2.6em; /* Ensure space for 2 lines */
        }
        .prompt-card-seller {
            font-size: 0.8rem; /* ปรับขนาด */
            color: #555ead; /* ปรับสี */
            margin-bottom: 10px;
            font-weight: 500;
        }
        .prompt-card-seller i { margin-right: 5px; }
        .prompt-card-description { /* ไม่แสดงในหน้า marketplace หลัก อาจจะแสดงในหน้า detail */ display: none; }
        .prompt-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto; 
            padding-top: 12px; /* ปรับ padding */
            border-top: 1px solid #f0f0f0;
        }
        .prompt-card-price {
            font-size: 1rem; /* ปรับขนาด */
            font-weight: 700;
            color: var(--success-color, #10b981);
        }
        .prompt-card-price i { margin-right: 5px; }
        .btn-view-prompt { /* (เหมือนใน index.php) */
            background: linear-gradient(135deg, var(--primary-color, #1E90FF), var(--secondary-color, #4169E1));
            color: white;
            padding: 7px 15px; /* ปรับ padding */
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem; /* ปรับขนาด */
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-view-prompt:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(var(--primary-color-rgb, 30, 144, 255), 0.25);
        }
        .no-prompts { /* (เหมือนใน index.php) */
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
            font-size: 1.1rem;
            grid-column: 1 / -1; /* Make it span all columns if grid is active */
        }
        .no-prompts i { font-size: 3rem; display: block; margin-bottom: 15px; color: #cbd5e1; }

        /* Pagination Styles (เหมือนใน index.php หรือ style.css) */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin: 30px 0; padding-bottom: 20px;}
        .pagination a, .pagination span { padding: 10px 15px; border: 1px solid #e5e7eb; background: white; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; font-size: 0.9em; text-decoration:none; color: #4f46e5; }
        .pagination a:hover, .pagination span.current { background: #4f46e5; color: white; border-color: #4f46e5; }
        .pagination span.disabled { opacity: 0.6; cursor: not-allowed; background: #f9fafb; color: #9ca3af; }
    </style>
</head>
<body>

    <header class="header" style="border-radius:0; /* ให้ header ของ marketplace เต็มความกว้างไปเลย */">
        <div class="user-menu">
            <?php if (!$isLoggedIn): ?>
                <a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a>
                <a href="login.php?redirect=marketplace.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
            <?php else: ?>
                <a href="profile.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['username']); ?></a>
                <span class="user-points-display"><i class="fas fa-coins"></i> <?php echo $user_points_display; ?> แต้ม</span>
                <a href="index.php"><i class="fas fa-home"></i> หน้าหลัก</a>
                <a href="submit_marketplace_prompt.php"><i class="fas fa-plus-circle"></i> ขาย Prompt</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                <?php if ($currentUser['member_type'] !== 'free'): ?>
                    <span class="member-badge">
                        <?php
                        $memberLabels = ['monthly' => 'รายเดือน', 'yearly' => 'รายปี'];
                        echo $memberLabels[$currentUser['member_type']] ?? '';
                        ?>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="online-status-container">
             </div>

        <div class="header-content">
            <h1><i class="fas fa-shopping-bag"></i> ตลาด Prompt</h1>
            <p>ค้นหาและซื้อ Prompt คุณภาพจากครีเอเตอร์คนอื่นๆ</p>
        </div>
    </header>

    <div class="marketplace-page-container">
        <div class="prompts-grid">
            <?php if (!empty($sellablePrompts)): ?>
                <?php foreach ($sellablePrompts as $prompt): ?>
                    <div class="prompt-card">
                        <div class="prompt-card-image">
                            <?php if (!empty($prompt['image_url']) && filter_var($prompt['image_url'], FILTER_VALIDATE_URL)): ?>
                                <img src="<?php echo htmlspecialchars($prompt['image_url']); ?>" alt="<?php echo htmlspecialchars($prompt['title']); ?>" loading="lazy" onerror="this.style.display='none'; this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#aaa;background-color:#f0f0f0;\'>ไม่มีรูปภาพ</div>';">
                            <?php else: ?>
                                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#aaa;background-color:#f0f0f0;">ไม่มีรูปภาพ</div>
                            <?php endif; ?>
                        </div>
                        <div class="prompt-card-content">
                            <h3 class="prompt-card-title"><?php echo htmlspecialchars($prompt['title']); ?></h3>
                            <p class="prompt-card-seller">
                                <i class="fas fa-user-circle"></i> โดย: <?php echo htmlspecialchars($prompt['seller_username'] ?? 'ไม่ระบุ'); ?>
                            </p>
                            <div class="prompt-card-footer">
                                <span class="prompt-card-price">
                                    <i class="fas fa-coins"></i> <?php echo htmlspecialchars($prompt['price_points']); ?> แต้ม
                                </span>
                                <a href="view_sellable_prompt.php?id=<?php echo $prompt['id']; ?>" class="btn-view-prompt">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-prompts">
                    <i class="fas fa-store-slash"></i>
                    <p>ยังไม่มี Prompt วางขายในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i> ก่อนหน้า</a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i> ก่อนหน้า</span>
            <?php endif; ?>

            <?php 
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            if ($startPage > 1) echo '<a href="?page=1">1</a>';
            if ($startPage > 2) echo '<span>...</span>';

            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'current' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages -1) echo '<span>...</span>'; ?>
            <?php if ($endPage < $totalPages) echo '<a href="?page='.$totalPages.'">'.$totalPages.'</a>'; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">ถัดไป <i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="disabled">ถัดไป <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        <div class="footer-links">
            <a href="index.php">หน้าหลัก</a> |
            <a href="marketplace.php">ตลาด Prompt</a> |
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">โปรไฟล์ของฉัน</a> |
                <a href="submit_marketplace_prompt.php">ขาย Prompt</a> |
            <?php else: ?>
                <a href="login.php">เข้าสู่ระบบ</a> |
                <a href="register.php">สมัครสมาชิก</a> |
            <?php endif; ?>
            <a href="#">เกี่ยวกับเรา</a> |
            <a href="#">ติดต่อเรา</a>
        </div>
        <div class="footer-socials">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
        <p class="footer-copyright">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?>. สงวนลิขสิทธิ์</p>
    </footer>

</body>
</html>