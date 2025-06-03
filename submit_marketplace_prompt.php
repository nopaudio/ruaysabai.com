<?php
require_once 'config.php'; // ไฟล์ config หลักของเรา

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง
if (!isUserLoggedIn()) {
    // ถ้ายังไม่ได้ล็อกอิน ให้ redirect ไปหน้า login
    header('Location: login.php?redirect=' . urlencode('submit_marketplace_prompt.php'));
    exit;
}

$currentUser = getCurrentUser(); // ดึงข้อมูลผู้ใช้ปัจจุบัน
$message = '';
$message_type = ''; // 'success' or 'error'

// ดึงราคา Prompt เริ่มต้นและค่าคอมมิชชั่น (ถ้าต้องการแสดงในฟอร์ม)
$promptManager = new PromptManager();
$defaultPrice = $promptManager->getSetting('default_prompt_price', 10);
$commissionRate = $promptManager->getSetting('commission_rate', 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_prompt'])) {
    // รับข้อมูลจากฟอร์ม
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $image_url = cleanInput($_POST['image_url'] ?? '');
    $actual_prompt = trim($_POST['actual_prompt'] ?? ''); // trim แต่ไม่ cleanInput มากไป เพราะอาจมีอักขระพิเศษ
    $price_points = filter_input(INPUT_POST, 'price_points', FILTER_VALIDATE_INT);
    $tags = cleanInput($_POST['tags'] ?? '');

    // Validation ข้อมูลเบื้องต้น
    if (empty($title) || empty($actual_prompt) || $price_points === false || $price_points <= 0) {
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน: หัวข้อ, Prompt จริง, และราคา (ต้องเป็นตัวเลขมากกว่า 0)';
        $message_type = 'error';
    } elseif (!empty($image_url) && !isValidImageUrl($image_url)) {
        $message = 'URL รูปภาพตัวอย่างไม่ถูกต้อง';
        $message_type = 'error';
    } else {
        // ส่งข้อมูลไปบันทึก
        $submittedPromptId = $promptManager->submitSellablePrompt(
            $currentUser['id'],
            $title,
            $description,
            $image_url,
            $actual_prompt,
            $price_points,
            $tags
        );

        if ($submittedPromptId) {
            $message = 'ส่ง Prompt ของคุณเพื่อรอการอนุมัติเรียบร้อยแล้ว! คุณสามารถตรวจสอบสถานะได้ที่หน้าโปรไฟล์ของคุณ';
            $message_type = 'success';
            // ล้างค่าในฟอร์มหลังจาก submit สำเร็จ (ถ้าต้องการ)
            $_POST = []; // ล้างค่า POST
        } else {
            $message = 'เกิดข้อผิดพลาดในการส่ง Prompt กรุณาลองใหม่อีกครั้ง';
            $message_type = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงขาย Prompt - <?php echo htmlspecialchars(SITE_TITLE); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
    <style>
        /* เพิ่ม style เฉพาะสำหรับหน้านี้ ถ้าต้องการ */
        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 25%, #c0cdda 50%, #a7b8c9 75%, #8fa0b5 100%);
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .page-container {
            max-width: 800px;
            margin: 20px auto;
            background: rgba(255, 255, 255, 0.98);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            color: var(--primary-color); /* ใช้ตัวแปรสีจาก style.css ถ้ามี */
            text-align: center;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .form-group label {
            font-weight: 600;
            color: #334155;
        }
        .form-control, .form-select { /* สำหรับ input, textarea, select */
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color, #1E90FF);
            box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.18);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .btn-submit-prompt { /* ปุ่ม submit */
            background: linear-gradient(135deg, var(--primary-color, #1E90FF) 0%, var(--secondary-color, #4169E1) 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-submit-prompt:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(30, 144, 255, 0.3);
        }
        .message-area {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        .message-area.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .message-area.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: -10px;
            margin-bottom: 15px;
            display: block;
        }
         .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color, #1E90FF);
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1><i class="fas fa-store"></i> ลงขาย Prompt ของคุณ</h1>
        </div>

        <?php if ($message): ?>
            <div class="message-area <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="submit_marketplace_prompt.php" method="POST">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> หัวข้อ Prompt:</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                <small class="form-text">ตั้งชื่อ Prompt ของคุณให้น่าสนใจและสื่อถึงผลลัพธ์</small>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> คำอธิบาย Prompt (ไม่บังคับ):</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <small class="form-text">อธิบายรายละเอียดเพิ่มเติมเกี่ยวกับ Prompt, สไตล์, หรือเทคนิคที่ใช้</small>
            </div>

            <div class="form-group">
                <label for="image_url"><i class="fas fa-image"></i> URL รูปภาพตัวอย่าง (ไม่บังคับ):</label>
                <input type="url" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                <small class="form-text">ใส่ลิงก์รูปภาพที่สร้างจาก Prompt นี้ (ถ้ามี)</small>
            </div>

            <div class="form-group">
                <label for="actual_prompt"><i class="fas fa-magic"></i> Prompt จริงที่จะขาย:</label>
                <textarea class="form-control" id="actual_prompt" name="actual_prompt" rows="5" required><?php echo htmlspecialchars($_POST['actual_prompt'] ?? ''); ?></textarea>
                <small class="form-text">ใส่ Prompt ทั้งหมดที่ผู้ซื้อจะได้รับ (ผู้ซื้อจะเห็นส่วนนี้หลังจากชำระแต้มแล้ว)</small>
            </div>

            <div class="form-group">
                <label for="price_points"><i class="fas fa-coins"></i> ราคาขาย (แต้ม):</label>
                <input type="number" class="form-control" id="price_points" name="price_points" value="<?php echo htmlspecialchars($_POST['price_points'] ?? $defaultPrice); ?>" min="1" required>
                <small class="form-text">กำหนดราคาเป็นแต้มที่ผู้ซื้อต้องจ่าย (ค่าบริการของเว็บ <?php echo $commissionRate; ?>% จะถูกหักจากแต้มนี้เมื่อขายได้)</small>
            </div>

            <div class="form-group">
                <label for="tags"><i class="fas fa-tags"></i> Tags (คั่นด้วยจุลภาค , ไม่บังคับ):</label>
                <input type="text" class="form-control" id="tags" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="เช่น anime, realistic, portrait, landscape">
                <small class="form-text">ใส่คีย์เวิร์ดที่เกี่ยวข้องเพื่อให้ค้นหาได้ง่ายขึ้น</small>
            </div>

            <button type="submit" name="submit_prompt" class="btn-submit-prompt">
                <i class="fas fa-paper-plane"></i> ส่ง Prompt เพื่อรออนุมัติ
            </button>
        </form>
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
    </div>
</body>
</html>