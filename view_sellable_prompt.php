<?php
require_once 'config.php';

$promptManager = new PromptManager();
$currentUser = getCurrentUser();
$currentUserId = $currentUser ? $currentUser['id'] : null;

$prompt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$promptData = null;
$purchaseMessage = '';
$purchaseMessageType = '';

if ($prompt_id > 0) {
    $promptData = $promptManager->getSellablePromptById($prompt_id, $currentUserId);
}

// จัดการการซื้อ Prompt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase_prompt' && $promptData && $currentUser) {
    if (!$promptData['is_owner'] && !$promptData['is_purchased']) {
        $result = $promptManager->purchasePrompt($currentUserId, $prompt_id);
        if ($result['success']) {
            $purchaseMessage = $result['message'];
            $purchaseMessageType = 'success';
            // โหลดข้อมูล Prompt ใหม่หลังจากซื้อสำเร็จ เพื่อให้แสดง actual_prompt
            $promptData = $promptManager->getSellablePromptById($prompt_id, $currentUserId);
        } else {
            $purchaseMessage = $result['message'];
            $purchaseMessageType = 'error';
        }
    } else {
        $purchaseMessage = 'คุณไม่สามารถซื้อ Prompt นี้ได้ (อาจจะเป็นเจ้าของหรือซื้อไปแล้ว)';
        $purchaseMessageType = 'error';
    }
}

if (!$promptData && $prompt_id > 0) { // แก้ไขเงื่อนไขเล็กน้อย: ถ้ามี prompt_id แต่หา promptData ไม่เจอ
    // แสดงข้อความว่าไม่พบ Prompt หรือ redirect
    // เพื่อความง่าย จะปล่อยให้ HTML ด้านล่างจัดการการแสดงผล "ไม่พบ Prompt"
}

$pageDataGlobal = getPageData();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $promptData ? htmlspecialchars($promptData['title']) : 'ไม่พบ Prompt'; ?> - <?php echo htmlspecialchars($pageDataGlobal['settings']['site_title'] ?? SITE_TITLE); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> 
    <style>
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding-bottom: 30px;
        }
        .prompt-view-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .prompt-view-image {
            width: 100%;
            max-height: 450px;
            object-fit: cover; 
            background-color: #e9ecef;
        }
        .prompt-view-content {
            padding: 30px;
        }
        .prompt-view-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .prompt-view-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #555;
        }
        .prompt-view-meta span { display: inline-flex; align-items: center; }
        .prompt-view-meta i { margin-right: 8px; color: #667eea; }
        .prompt-view-description {
            font-size: 1rem;
            color: #444;
            line-height: 1.7;
            margin-bottom: 25px;
            white-space: pre-wrap;
        }
        .prompt-view-tags { margin-bottom: 25px; }
        .prompt-view-tags .tag { display: inline-block; background-color: #e0e7ff; color: #4f46e5; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; margin-right: 8px; margin-bottom: 8px; font-weight: 500; }
        .actual-prompt-section { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-top: 25px; }
        .actual-prompt-section h4 { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 15px; }
        .actual-prompt-text { background-color: #fff; padding: 15px; border-radius: 6px; font-family: 'SF Mono', Monaco, Consolas, 'Courier New', monospace; font-size: 0.9rem; line-height: 1.6; color: #2c3e50; white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto; border: 1px solid #d1d5db; }
        .btn-purchase-prompt, .btn-copy-prompt, .btn-topup-points {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; padding: 12px 25px; border-radius: 8px; text-decoration: none;
            font-size: 1.1rem; font-weight: 600; transition: all 0.3s ease;
            border: none; cursor: pointer; margin-top: 10px; /* ลด margin-top ของปุ่มซื้อ */
        }
        .btn-purchase-prompt:hover, .btn-copy-prompt:hover, .btn-topup-points:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25); }
        .btn-copy-prompt { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .btn-topup-points { 
            background: linear-gradient(135deg, #f59e0b, #f97316); 
            margin-top: 10px; /* ให้มีระยะห่างเท่าปุ่มซื้อ */
        }
        .btn-topup-points:hover {
             box-shadow: 0 8px 20px rgba(245, 158, 11, 0.25);
        }
        .purchase-message { padding: 15px; margin: 20px 0; border-radius: 8px; text-align: center; font-weight: 500; }
        .purchase-message.success { background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .purchase-message.error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .back-to-market-link { display: block; text-align: center; margin-top: 30px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 8px; max-width: 200px; margin-left: auto; margin-right: auto; }
        .back-to-market-link:hover { background-color: #5a6268; }
        .prompt-price-display { font-size: 1.5rem; font-weight: 700; color: #10b981; margin-bottom: 15px; text-align: center; } /* ลด margin-bottom */
        .prompt-price-display i { margin-right: 8px; }
        .action-buttons-container {
            display: flex;
            flex-direction: column; /* ให้ปุ่มเรียงต่อกันแนวตั้ง */
            align-items: center; /* จัดกลาง */
            gap: 10px; /* ระยะห่างระหว่างปุ่ม */
        }
    </style>
</head>
<body>
    <?php // include_once 'partials/header.php'; ?>

    <div class="prompt-view-container">
        <?php if ($promptData): ?>
            <?php if (!empty($promptData['image_url']) && filter_var($promptData['image_url'], FILTER_VALIDATE_URL)): ?>
                <img src="<?php echo htmlspecialchars($promptData['image_url']); ?>" alt="<?php echo htmlspecialchars($promptData['title']); ?>" class="prompt-view-image" onerror="this.style.display='none';">
            <?php endif; ?>

            <div class="prompt-view-content">
                <h1 class="prompt-view-title"><?php echo htmlspecialchars($promptData['title']); ?></h1>
                
                <div class="prompt-view-meta">
                    <span><i class="fas fa-user-circle"></i>ผู้ขาย: <?php echo htmlspecialchars($promptData['seller_username'] ?? 'ไม่ระบุ'); ?></span>
                    <span><i class="fas fa-calendar-alt"></i>ลงขายเมื่อ: <?php echo date("d M Y", strtotime($promptData['created_at'])); ?></span>
                    <span><i class="fas fa-fire"></i>ขายไปแล้ว: <?php echo htmlspecialchars($promptData['total_sales'] ?? 0); ?> ครั้ง</span>
                </div>

                <?php if ($purchaseMessage): ?>
                    <div class="purchase-message <?php echo $purchaseMessageType; ?>">
                        <?php echo htmlspecialchars($purchaseMessage); ?>
                    </div>
                <?php endif; ?>

                <h4 style="font-weight:600; margin-top:20px; margin-bottom:8px;">คำอธิบาย Prompt:</h4>
                <p class="prompt-view-description">
                    <?php echo nl2br(htmlspecialchars($promptData['description'] ?? 'ไม่มีคำอธิบาย')); ?>
                </p>

                <?php if (!empty($promptData['tags'])): ?>
                    <div class="prompt-view-tags">
                        <h4 style="font-weight:600; margin-bottom:8px;">Tags:</h4>
                        <?php 
                        $tags = explode(',', $promptData['tags']);
                        foreach ($tags as $tag) {
                            echo '<span class="tag">' . htmlspecialchars(trim($tag)) . '</span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($promptData['is_purchased'] || $promptData['is_owner']): ?>
                    <div class="actual-prompt-section">
                        <h4><i class="fas fa-magic"></i> Prompt จริงของคุณ:</h4>
                        <div class="actual-prompt-text" id="actualPromptText">
<?php echo htmlspecialchars($promptData['actual_prompt'] ?? 'ไม่สามารถแสดง Prompt ได้'); ?>
                        </div>
                        <button class="btn-copy-prompt" onclick="copyPromptToClipboard()">
                            <i class="fas fa-copy"></i> คัดลอก Prompt
                        </button>
                    </div>
                <?php else: // ถ้ายังไม่ได้ซื้อ และไม่ใช่เจ้าของ ?>
                    <div style="text-align:center; margin-top: 30px;">
                        <div class="prompt-price-display">
                            <i class="fas fa-coins"></i> <?php echo htmlspecialchars($promptData['price_points']); ?> แต้ม
                        </div>
                        <div class="action-buttons-container"> 
                            <?php if ($currentUser): ?>
                                <form action="view_sellable_prompt.php?id=<?php echo $prompt_id; ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="purchase_prompt">
                                    <button type="submit" class="btn-purchase-prompt">
                                        <i class="fas fa-shopping-cart"></i> ซื้อ Prompt นี้
                                    </button>
                                </form>
                                <?php 
                                if ($purchaseMessageType === 'error' && strpos($purchaseMessage, 'แต้มของคุณไม่เพียงพอ') !== false): 
                                ?>
                                    <a href="top_up_points.php" class="btn-topup-points"> 
                                        <i class="fas fa-plus-circle"></i> เติมแต้มตอนนี้
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p><a href="login.php?redirect=<?php echo urlencode(basename(__FILE__) . '?id=' . $prompt_id); ?>" class="btn-purchase-prompt">กรุณาเข้าสู่ระบบเพื่อซื้อ</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <a href="marketplace.php" class="back-to-market-link"><i class="fas fa-arrow-left"></i> กลับไปตลาด Prompt</a>
            </div>

        <?php else: ?>
            <div class="prompt-view-content" style="text-align:center;">
                <h1><i class="fas fa-exclamation-triangle"></i> ไม่พบ Prompt</h1>
                <p>Prompt ที่คุณต้องการอาจจะถูกลบไปแล้ว หรือ URL ไม่ถูกต้อง</p>
                <a href="marketplace.php" class="back-to-market-link"><i class="fas fa-arrow-left"></i> กลับไปตลาด Prompt</a>
            </div>
        <?php endif; ?>
    </div>

    <?php // include_once 'partials/footer.php'; ?>
<script>
function copyPromptToClipboard() {
    const promptTextElement = document.getElementById('actualPromptText');
    if (promptTextElement) {
        const textToCopy = promptTextElement.innerText || promptTextElement.textContent;
        navigator.clipboard.writeText(textToCopy).then(() => {
            alert('คัดลอก Prompt เรียบร้อยแล้ว!');
        }).catch(err => {
            console.error('ไม่สามารถคัดลอก Prompt: ', err);
            alert('เกิดข้อผิดพลาดในการคัดลอก Prompt');
        });
    }
}
</script>
</body>
</html>