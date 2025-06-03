<?php
require_once 'config.php'; // ไฟล์ config หลักของเรา

$currentUser = getCurrentUser(); // ดึงข้อมูลผู้ใช้ปัจจุบัน (ถ้ามีการ login)
$pageDataGlobal = getPageData(); // ดึงข้อมูล global สำหรับ header/footer

// ถ้ายังไม่ได้ล็อกอิน อาจจะ redirect ไปหน้า login ก่อน (optional)
if (!$currentUser) {
    header('Location: login.php?redirect=' . urlencode(basename(__FILE__)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เติมแต้ม - <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <style>
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding-top: 20px;
            padding-bottom: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* ให้ header และ footer (ถ้ามี) จัดเรียงถูก */
        }
        .page-container {
            max-width: 700px;
            margin: 20px auto;
            background: white;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .page-header h1 {
            color: var(--primary-color, #1E90FF);
            margin-bottom: 25px;
            font-size: 2rem;
            font-weight: 700;
        }
        .page-header h1 i {
            margin-right: 10px;
        }
        .content-message p {
            font-size: 1.1rem;
            color: #333;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .contact-admin {
            background-color: #e9f5ff;
            border: 1px solid #b3d8ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            color: #004085;
        }
        .contact-admin strong {
            display: block;
            margin-bottom: 8px;
        }
        .action-links a {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .action-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.25);
        }
        .action-links a i {
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <?php // ถ้าคุณมี header กลาง (เช่น navigation bar) สามารถ include มาตรงนี้ได้
        // เช่น include_once 'partials/main_header.php'; 
        // หรือถ้า header อยู่ใน index.php อาจจะต้องทำ header แยก
    ?>
     <div style="background-color: #fff; padding: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; margin-bottom: 20px;">
        <a href="index.php" style="text-decoration: none; color: #333; font-size: 1.5rem; font-weight: 600;">
            <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?>
        </a>
        <?php if ($currentUser): ?>
            <span style="margin-left: 20px;">สวัสดี, <?php echo htmlspecialchars($currentUser['username']); ?> | 
                <a href="profile.php">โปรไฟล์</a> | 
                <a href="logout.php">ออกจากระบบ</a>
            </span>
        <?php endif; ?>
    </div>


    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-coins"></i> เติมแต้ม</h1>
        </div>

        <div class="content-message">
            <p><i class="fas fa-tools"></i> ขณะนี้ระบบเติมแต้มอัตโนมัติกำลังอยู่ในระหว่างการพัฒนา</p>
            <p>เรากำลังพยายามอย่างเต็มที่เพื่อให้คุณสามารถเติมแต้มได้อย่างสะดวกและรวดเร็วในเร็วๆ นี้</p>
        </div>

        <div class="contact-admin">
            <strong>ต้องการเติมแต้มทันที?</strong>
            <p>คุณสามารถติดต่อผู้ดูแลระบบเพื่อทำการเติมแต้มด้วยตนเองได้</p>
            <p>อีเมล: [ใส่อีเมลติดต่อ Admin ที่นี่] หรือ Line ID: [ใส่ Line ID Admin ที่นี่]</p>
        </div>
        
        <div class="action-links">
            <a href="marketplace.php"><i class="fas fa-store"></i> กลับไปตลาด Prompt</a>
            <a href="index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a>
        </div>
    </div>

    <?php // ถ้าคุณมี footer กลาง สามารถ include มาตรงนี้ได้
        // include_once 'partials/main_footer.php'; 
    ?>
     <div style="text-align: center; margin-top: 30px; padding: 15px; background-color: #333; color: #fff;">
        &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?>. All rights reserved.
    </div>

</body>
</html>