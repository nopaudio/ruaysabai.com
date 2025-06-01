<?php
require_once 'config.php';

// ตรวจสอบการเข้าสู่ระบบ admin
session_start();

// ถ้ายังไม่ได้ login และไม่ใช่การ submit login form
if (!isAdmin() && !isset($_POST['admin_password'])) {
    // แสดงฟอร์ม login
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - AI Prompt Generator Pro</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                background: rgba(255, 255, 255, 0.98);
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
                text-align: center;
            }
            
            .login-header {
                margin-bottom: 30px;
            }
            
            .login-header i {
                font-size: 3em;
                color: #667eea;
                margin-bottom: 20px;
            }
            
            .login-header h1 {
                color: #333;
                font-size: 1.8em;
                margin-bottom: 10px;
            }
            
            .form-group {
                margin-bottom: 20px;
                text-align: left;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #555;
                font-weight: 600;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            
            .login-btn {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                border: none;
                padding: 15px 30px;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
                transition: all 0.3s ease;
            }
            
            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            }
            
            .error-message {
                background: rgba(255, 107, 107, 0.1);
                border: 1px solid rgba(255, 107, 107, 0.3);
                color: #ff6b6b;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: none;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h1>Admin Login</h1>
                <p style="color: #666;">กรุณาเข้าสู่ระบบเพื่อจัดการเว็บไซต์</p>
            </div>
            
            <div class="error-message" id="error-message">
                <i class="fas fa-exclamation-circle"></i> รหัสผ่านไม่ถูกต้อง
            </div>
            
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="admin_password">รหัสผ่าน Admin</label>
                    <input type="password" id="admin_password" name="admin_password" required autofocus>
                </div>
                
                <button type="submit" name="admin_login" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                </button>
            </form>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
        <script>
            document.getElementById('error-message').style.display = 'block';
            setTimeout(() => {
                document.getElementById('error-message').style.display = 'none';
            }, 3000);
        </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// ตรวจสอบการ login
if (isset($_POST['admin_login']) && isset($_POST['admin_password'])) {
    if (adminLogin($_POST['admin_password'])) {
        header('Location: admin.php');
        exit;
    } else {
        header('Location: admin.php?error=1');
        exit;
    }
}

// ตรวจสอบว่า login แล้วหรือยัง
if (!isAdmin()) {
    header('Location: admin.php');
    exit;
}

// จัดการ AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $promptManager = new PromptManager();
    $db = Database::getInstance();
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'save_example':
                $title = cleanInput($_POST['title']);
                $prompt = cleanInput($_POST['prompt']);
                $icon = cleanInput($_POST['icon']) ?: 'fas fa-image';
                $id = isset($_POST['id']) ? intval($_POST['id']) : null;
                
                if ($id) {
                    $result = $db->update('prompt_examples', [
                        'title' => $title,
                        'prompt' => $prompt,
                        'icon' => $icon
                    ], "id = $id");
                } else {
                    $result = $db->insert('prompt_examples', [
                        'title' => $title,
                        'prompt' => $prompt,
                        'icon' => $icon
                    ]);
                }
                
                jsonResponse($result, $result ? 'บันทึกข้อมูลเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาด');
                break;
                
            case 'delete_example':
                $id = intval($_POST['id']);
                $result = $db->delete('prompt_examples', "id = $id");
                jsonResponse($result, $result ? 'ลบข้อมูลเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาด');
                break;
                
            case 'save_gallery':
                $title = cleanInput($_POST['title']);
                $description = cleanInput($_POST['description']);
                $image_url = cleanInput($_POST['image_url']);
                $prompt = cleanInput($_POST['prompt']);
                $icon = cleanInput($_POST['icon']) ?: 'fas fa-image';
                $id = isset($_POST['id']) ? intval($_POST['id']) : null;
                
                if ($id) {
                    $result = $db->update('gallery_items', [
                        'title' => $title,
                        'description' => $description,
                        'image_url' => $image_url,
                        'prompt' => $prompt,
                        'icon' => $icon
                    ], "id = $id");
                } else {
                    $result = $db->insert('gallery_items', [
                        'title' => $title,
                        'description' => $description,
                        'image_url' => $image_url,
                        'prompt' => $prompt,
                        'icon' => $icon
                    ]);
                }
                
                jsonResponse($result, $result ? 'บันทึกข้อมูลเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาด');
                break;
                
            case 'delete_gallery':
                $id = intval($_POST['id']);
                $result = $db->delete('gallery_items', "id = $id");
                jsonResponse($result, $result ? 'ลบข้อมูลเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาด');
                break;
                
            case 'save_settings':
                $success = true;
                foreach ($_POST['settings'] as $key => $value) {
                    if (!$promptManager->setSetting($key, cleanInput($value))) {
                        $success = false;
                        break;
                    }
                }
                jsonResponse($success, $success ? 'บันทึกการตั้งค่าเรียบร้อยแล้ว' : 'เกิดข้อผิดพลาดในการบันทึก');
                break;
                
            case 'get_data':
                $examples = $promptManager->getAllExamples();
                $gallery = $promptManager->getAllGalleryItems();
                $stats = $promptManager->getStats();
                
                jsonResponse(true, 'ดึงข้อมูลสำเร็จ', [
                    'examples' => $examples,
                    'gallery' => $gallery,
                    'stats' => $stats,
                    'settings' => [
                        'site_title' => $promptManager->getSetting('site_title'),
                        'site_description' => $promptManager->getSetting('site_description'),
                        'online_count' => $promptManager->getSetting('online_count'),
                        'placeholder_title' => $promptManager->getSetting('placeholder_title'),
                        'placeholder_description' => $promptManager->getSetting('placeholder_description'),
                        'gallery_title' => $promptManager->getSetting('gallery_title'),
                        'gallery_description' => $promptManager->getSetting('gallery_description')
                    ]
                ]);
                break;
                
            case 'logout':
                adminLogout();
                jsonResponse(true, 'ออกจากระบบสำเร็จ');
                break;
                
            default:
                jsonResponse(false, 'Invalid action');
        }
    } catch (Exception $e) {
        error_log('Admin action error: ' . $e->getMessage());
        jsonResponse(false, 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    exit;
}

// ดึงข้อมูลเริ่มต้น
$promptManager = new PromptManager();
$pageData = [
    'examples' => $promptManager->getAllExamples(),
    'gallery' => $promptManager->getAllGalleryItems(),
    'stats' => $promptManager->getStats(),
    'settings' => [
        'site_title' => $promptManager->getSetting('site_title'),
        'site_description' => $promptManager->getSetting('site_description'),
        'online_count' => $promptManager->getSetting('online_count'),
        'placeholder_title' => $promptManager->getSetting('placeholder_title'),
        'placeholder_description' => $promptManager->getSetting('placeholder_description'),
        'gallery_title' => $promptManager->getSetting('gallery_title'),
        'gallery_description' => $promptManager->getSetting('gallery_description')
    ]
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Backend - AI Prompt Generator Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .admin-header h1 {
            color: #667eea;
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .admin-header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            padding: 15px 30px;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #667eea;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .nav-btn.active, .nav-btn:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }
        
        .admin-section {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .admin-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section-title {
            font-size: 1.8em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }
        
        .btn-danger:hover {
            box-shadow: 0 15px 35px rgba(255, 107, 107, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #20bf6b, #26a69a);
            box-shadow: 0 10px 25px rgba(32, 191, 107, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 15px 35px rgba(32, 191, 107, 0.4);
        }
        
        .items-grid {
            display: grid;
            gap: 20px;
            margin-top: 30px;
        }
        
        .item-card {
            background: rgba(248, 250, 252, 0.8);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        
        .item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
        }
        
        .item-title {
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        
        .item-actions {
            display: flex;
            gap: 10px;
        }
        
        .item-content {
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 10px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 13px;
            line-height: 1.5;
            color: #555;
            border: 1px solid #e1e5e9;
            margin-bottom: 15px;
            max-height: 120px;
            overflow-y: auto;
        }
        
        .gallery-item-card {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            align-items: start;
        }
        
        .gallery-image-preview {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e1e5e9;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            font-size: 1.5em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .image-preview {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid #e1e5e9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 800;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            color: #666;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert.success {
            background: rgba(32, 191, 107, 0.1);
            border: 1px solid rgba(32, 191, 107, 0.3);
            color: #20bf6b;
        }
        
        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            
            .admin-header {
                padding: 20px;
            }
            
            .admin-header h1 {
                font-size: 1.8em;
            }
            
            .logout-btn {
                position: static;
                margin-top: 20px;
            }
            
            .admin-nav {
                flex-direction: column;
            }
            
            .nav-btn {
                justify-content: center;
                padding: 12px 20px;
            }
            
            .admin-section {
                padding: 20px;
            }
            
            .gallery-item-card {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .modal-content {
                padding: 20px;
                margin: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </button>
            <h1><i class="fas fa-shield-alt"></i> Admin Backend</h1>
            <p>จัดการเนื้อหาและการตั้งค่าของ AI Prompt Generator Pro</p>
        </div>
        
        <div class="admin-nav">
            <button class="nav-btn active" onclick="showSection('dashboard')">
                <i class="fas fa-chart-bar"></i> Dashboard
            </button>
            <button class="nav-btn" onclick="showSection('examples')">
                <i class="fas fa-star"></i> Prompt ยอดนิยม
            </button>
            <button class="nav-btn" onclick="showSection('gallery')">
                <i class="fas fa-images"></i> แกลเลอรี่
            </button>
            <button class="nav-btn" onclick="showSection('messages')">
                <i class="fas fa-comment"></i> ข้อความ
            </button>
            <button class="nav-btn" onclick="showSection('settings')">
                <i class="fas fa-cog"></i> ตั้งค่า
            </button>
        </div>
        
        <!-- Dashboard Section -->
        <div id="dashboard" class="admin-section active">
            <div class="section-title">
                <span><i class="fas fa-chart-bar"></i> Dashboard</span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number" id="total-examples"><?php echo $pageData['stats']['total_examples']; ?></span>
                    <div class="stat-label">Prompt ยอดนิยม</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="total-gallery"><?php echo $pageData['stats']['total_gallery']; ?></span>
                    <div class="stat-label">รายการแกลเลอรี่</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="total-user-prompts"><?php echo $pageData['stats']['total_user_prompts']; ?></span>
                    <div class="stat-label">Prompt ที่ผู้ใช้สร้าง</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="today-prompts"><?php echo $pageData['stats']['today_prompts']; ?></span>
                    <div class="stat-label">Prompt วันนี้</div>
                </div>
            </div>
        </div>
        
        <!-- Examples Section -->
        <div id="examples" class="admin-section">
            <div class="section-title">
                <span><i class="fas fa-star"></i> จัดการ Prompt ยอดนิยม</span>
                <button class="btn" onclick="openExampleModal()">
                    <i class="fas fa-plus"></i> เพิ่มใหม่
                </button>
            </div>
            
            <div class="items-grid" id="examples-list">
                <!-- Examples will be loaded here -->
            </div>
        </div>
        
        <!-- Gallery Section -->
        <div id="gallery" class="admin-section">
            <div class="section-title">
                <span><i class="fas fa-images"></i> จัดการแกลเลอรี่ Prompt</span>
                <button class="btn" onclick="openGalleryModal()">
                    <i class="fas fa-plus"></i> เพิ่มใหม่
                </button>
            </div>
            
            <div class="items-grid" id="gallery-list">
                <!-- Gallery items will be loaded here -->
            </div>
        </div>
        
        <!-- Messages Section -->
        <div id="messages" class="admin-section">
            <div class="section-title">
                <span><i class="fas fa-comment"></i> จัดการข้อความ</span>
            </div>
            
            <div class="form-group">
                <label>ข้อความแสดงในส่วน "เริ่มสร้าง Prompt ของคุณ"</label>
                <input type="text" id="placeholder-title" value="<?php echo htmlspecialchars($pageData['settings']['placeholder_title']); ?>" placeholder="หัวข้อ">
            </div>
            
            <div class="form-group">
                <label>คำอธิบาย</label>
                <textarea id="placeholder-description" placeholder="คำอธิบาย"><?php echo htmlspecialchars($pageData['settings']['placeholder_description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>ข้อความหัวข้อแกลเลอรี่</label>
                <input type="text" id="gallery-title" value="<?php echo htmlspecialchars($pageData['settings']['gallery_title']); ?>" placeholder="หัวข้อแกลเลอรี่">
            </div>
            
            <div class="form-group">
                <label>คำอธิบายแกลเลอรี่</label>
                <textarea id="gallery-description" placeholder="คำอธิบายแกลเลอรี่"><?php echo htmlspecialchars($pageData['settings']['gallery_description']); ?></textarea>
            </div>
            
            <button class="btn btn-success" onclick="saveMessages()">
                <i class="fas fa-save"></i> บันทึกข้อความ
            </button>
        </div>
        
        <!-- Settings Section -->
        <div id="settings" class="admin-section">
            <div class="section-title">
                <span><i class="fas fa-cog"></i> ตั้งค่าระบบ</span>
            </div>
            
            <div class="form-group">
                <label>จำนวนผู้ใช้ออนไลน์ (แสดงผล)</label>
                <input type="number" id="online-count" value="<?php echo htmlspecialchars($pageData['settings']['online_count']); ?>" min="50" max="500">
            </div>
            
            <div class="form-group">
                <label>หัวข้อหลักของเว็บไซต์</label>
                <input type="text" id="site-title" value="<?php echo htmlspecialchars($pageData['settings']['site_title']); ?>" placeholder="หัวข้อเว็บไซต์">
            </div>
            
            <div class="form-group">
                <label>คำอธิบายหัวข้อ</label>
                <textarea id="site-description" placeholder="คำอธิบายเว็บไซต์"><?php echo htmlspecialchars($pageData['settings']['site_description']); ?></textarea>
            </div>
            
            <button class="btn btn-success" onclick="saveSettings()">
                <i class="fas fa-save"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </div>
    
    <!-- Example Modal -->
    <div id="example-modal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('example-modal')">&times;</button>
            <div class="modal-header">
                <i class="fas fa-star"></i> <span id="example-modal-title">เพิ่ม Prompt ยอดนิยม</span>
            </div>
            
            <div class="form-group">
                <label>ชื่อ/หัวข้อ</label>
                <input type="text" id="example-title" placeholder="เช่น สาวสวยในสวนดอกไม้">
            </div>
            
            <div class="form-group">
                <label>Prompt</label>
                <textarea id="example-prompt" placeholder="ใส่ Prompt ที่ต้องการ"></textarea>
            </div>
            
            <div class="form-group">
                <label>ไอคอน (Font Awesome class)</label>
                <input type="text" id="example-icon" placeholder="เช่น fas fa-image" value="fas fa-image">
            </div>
            
            <button class="btn btn-success" onclick="saveExample()">
                <i class="fas fa-save"></i> บันทึก
            </button>
        </div>
    </div>
    
    <!-- Gallery Modal -->
    <div id="gallery-modal" class="modal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeModal('gallery-modal')">&times;</button>
            <div class="modal-header">
                <i class="fas fa-images"></i> <span id="gallery-modal-title">เพิ่มรายการแกลเลอรี่</span>
            </div>
            
            <div class="form-group">
                <label>ชื่อ/หัวข้อ</label>
                <input type="text" id="gallery-title-input" placeholder="เช่น AI Robot Portrait">
            </div>
            
            <div class="form-group">
                <label>คำอธิบาย</label>
                <input type="text" id="gallery-desc" placeholder="เช่น หุ่นยนต์ AI แบบไซเบอร์พังค์">
            </div>
            
            <div class="form-group">
                <label>URL รูปภาพ</label>
                <input type="url" id="gallery-image" placeholder="https://example.com/image.jpg" onchange="previewImage(this.value)">
                <img id="gallery-preview" class="image-preview" style="display: none;">
            </div>
            
            <div class="form-group">
                <label>Prompt</label>
                <textarea id="gallery-prompt" placeholder="ใส่ Prompt ที่ต้องการ"></textarea>
            </div>
            
            <div class="form-group">
                <label>ไอคอน (Font Awesome class)</label>
                <input type="text" id="gallery-icon" placeholder="เช่น fas fa-robot" value="fas fa-image">
            </div>
            
            <button class="btn btn-success" onclick="saveGalleryItem()">
                <i class="fas fa-save"></i> บันทึก
            </button>
        </div>
    </div>
    
    <script>
        // Data from PHP
        let examplesData = <?php echo json_encode($pageData['examples']); ?>;
        let galleryData = <?php echo json_encode($pageData['gallery']); ?>;
        let currentEditingExample = null;
        let currentEditingGallery = null;
        
        // Navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.admin-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav buttons
            document.querySelectorAll('.nav-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked button
            event.target.closest('.nav-btn').classList.add('active');
            
            // Load data for specific sections
            if (sectionId === 'examples') {
                loadExamples();
            } else if (sectionId === 'gallery') {
                loadGallery();
            } else if (sectionId === 'dashboard') {
                updateStats();
            }
        }
        
        // Modal Functions
        function openExampleModal(editId = null) {
            currentEditingExample = editId;
            const modal = document.getElementById('example-modal');
            const title = document.getElementById('example-modal-title');
            
            if (editId) {
                const example = examplesData.find(e => e.id == editId);
                title.textContent = 'แก้ไข Prompt ยอดนิยม';
                document.getElementById('example-title').value = example.title;
                document.getElementById('example-prompt').value = example.prompt;
                document.getElementById('example-icon').value = example.icon;
            } else {
                title.textContent = 'เพิ่ม Prompt ยอดนิยม';
                document.getElementById('example-title').value = '';
                document.getElementById('example-prompt').value = '';
                document.getElementById('example-icon').value = 'fas fa-image';
            }
            
            modal.classList.add('active');
        }
        
        function openGalleryModal(editId = null) {
            currentEditingGallery = editId;
            const modal = document.getElementById('gallery-modal');
            const title = document.getElementById('gallery-modal-title');
            const preview = document.getElementById('gallery-preview');
            
            if (editId) {
                const item = galleryData.find(g => g.id == editId);
                title.textContent = 'แก้ไขรายการแกลเลอรี่';
                document.getElementById('gallery-title-input').value = item.title;
                document.getElementById('gallery-desc').value = item.description;
                document.getElementById('gallery-image').value = item.image_url;
                document.getElementById('gallery-prompt').value = item.prompt;
                document.getElementById('gallery-icon').value = item.icon;
                preview.src = item.image_url;
                preview.style.display = 'block';
            } else {
                title.textContent = 'เพิ่มรายการแกลเลอรี่';
                document.getElementById('gallery-title-input').value = '';
                document.getElementById('gallery-desc').value = '';
                document.getElementById('gallery-image').value = '';
                document.getElementById('gallery-prompt').value = '';
                document.getElementById('gallery-icon').value = 'fas fa-image';
                preview.style.display = 'none';
            }
            
            modal.classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Preview image
        function previewImage(url) {
            const preview = document.getElementById('gallery-preview');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
                preview.onerror = function() {
                    this.style.display = 'none';
                    showAlert('error', 'ไม่สามารถโหลดรูปภาพได้');
                };
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Save Functions
        function saveExample() {
            const title = document.getElementById('example-title').value.trim();
            const prompt = document.getElementById('example-prompt').value.trim();
            const icon = document.getElementById('example-icon').value.trim() || 'fas fa-image';
            
            if (!title || !prompt) {
                showAlert('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_example');
            formData.append('title', title);
            formData.append('prompt', prompt);
            formData.append('icon', icon);
            
            if (currentEditingExample) {
                formData.append('id', currentEditingExample);
            }
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('example-modal');
                    refreshData();
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                console.error('Error:', error);
            });
        }
        
        function saveGalleryItem() {
            const title = document.getElementById('gallery-title-input').value.trim();
            const description = document.getElementById('gallery-desc').value.trim();
            const image_url = document.getElementById('gallery-image').value.trim();
            const prompt = document.getElementById('gallery-prompt').value.trim();
            const icon = document.getElementById('gallery-icon').value.trim() || 'fas fa-image';
            
            if (!title || !description || !image_url || !prompt) {
                showAlert('error', 'กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'save_gallery');
            formData.append('title', title);
            formData.append('description', description);
            formData.append('image_url', image_url);
            formData.append('prompt', prompt);
            formData.append('icon', icon);
            
            if (currentEditingGallery) {
                formData.append('id', currentEditingGallery);
            }
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('gallery-modal');
                    refreshData();
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                console.error('Error:', error);
            });
        }
        
        function saveMessages() {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('settings[placeholder_title]', document.getElementById('placeholder-title').value);
            formData.append('settings[placeholder_description]', document.getElementById('placeholder-description').value);
            formData.append('settings[gallery_title]', document.getElementById('gallery-title').value);
            formData.append('settings[gallery_description]', document.getElementById('gallery-description').value);
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                console.error('Error:', error);
            });
        }
        
        function saveSettings() {
            const formData = new FormData();
            formData.append('action', 'save_settings');
            formData.append('settings[online_count]', document.getElementById('online-count').value);
            formData.append('settings[site_title]', document.getElementById('site-title').value);
            formData.append('settings[site_description]', document.getElementById('site-description').value);
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                console.error('Error:', error);
            });
        }
        
        // Delete Functions
        function deleteExample(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบ Prompt นี้?')) {
                const formData = new FormData();
                formData.append('action', 'delete_example');
                formData.append('id', id);
                
                fetch('admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        refreshData();
                        showAlert('success', data.message);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    console.error('Error:', error);
                });
            }
        }
        
        function deleteGalleryItem(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบรายการแกลเลอรี่นี้?')) {
                const formData = new FormData();
                formData.append('action', 'delete_gallery');
                formData.append('id', id);
                
                fetch('admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        refreshData();
                        showAlert('success', data.message);
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    console.error('Error:', error);
                });
            }
        }
        
        // Load Functions
        function loadExamples() {
            const container = document.getElementById('examples-list');
            container.innerHTML = '';
            
            if (examplesData.length === 0) {
                container.innerHTML = '<div class="placeholder-message"><i class="fas fa-inbox"></i><p>ยังไม่มี Prompt ยอดนิยม</p></div>';
                return;
            }
            
            examplesData.forEach(example => {
                const card = document.createElement('div');
                card.className = 'item-card';
                card.innerHTML = `
                    <div class="item-header">
                        <div class="item-title">
                            <i class="${example.icon}"></i> ${example.title}
                        </div>
                        <div class="item-actions">
                            <button class="btn" onclick="openExampleModal(${example.id})" style="padding: 8px 15px; font-size: 12px;">
                                <i class="fas fa-edit"></i> แก้ไข
                            </button>
                            <button class="btn btn-danger" onclick="deleteExample(${example.id})" style="padding: 8px 15px; font-size: 12px;">
                                <i class="fas fa-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                    <div class="item-content">${example.prompt}</div>
                `;
                container.appendChild(card);
            });
        }
        
        function loadGallery() {
            const container = document.getElementById('gallery-list');
            container.innerHTML = '';
            
            if (galleryData.length === 0) {
                container.innerHTML = '<div class="placeholder-message"><i class="fas fa-images"></i><p>ยังไม่มีรายการแกลเลอรี่</p></div>';
                return;
            }
            
            galleryData.forEach(item => {
                const card = document.createElement('div');
                card.className = 'item-card gallery-item-card';
                card.innerHTML = `
                    <img src="${item.image_url}" alt="${item.title}" class="gallery-image-preview" loading="lazy" onerror="this.src='https://via.placeholder.com/200x120?text=Image+Not+Found'">
                    <div>
                        <div class="item-header">
                            <div class="item-title">
                                <i class="${item.icon}"></i> ${item.title}
                            </div>
                            <div class="item-actions">
                                <button class="btn" onclick="openGalleryModal(${item.id})" style="padding: 8px 15px; font-size: 12px;">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </button>
                                <button class="btn btn-danger" onclick="deleteGalleryItem(${item.id})" style="padding: 8px 15px; font-size: 12px;">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            </div>
                        </div>
                        <div style="color: #666; font-style: italic; margin-bottom: 10px;">${item.description}</div>
                        <div class="item-content">${item.prompt}</div>
                    </div>
                `;
                container.appendChild(card);
            });
        }
        
        // Refresh data from server
        function refreshData() {
            const formData = new FormData();
            formData.append('action', 'get_data');
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    examplesData = data.data.examples;
                    galleryData = data.data.gallery;
                    
                    // Update stats
                    document.getElementById('total-examples').textContent = data.data.stats.total_examples;
                    document.getElementById('total-gallery').textContent = data.data.stats.total_gallery;
                    document.getElementById('total-user-prompts').textContent = data.data.stats.total_user_prompts;
                    document.getElementById('today-prompts').textContent = data.data.stats.today_prompts;
                    
                    // Reload current section
                    const activeSection = document.querySelector('.admin-section.active');
                    if (activeSection.id === 'examples') {
                        loadExamples();
                    } else if (activeSection.id === 'gallery') {
                        loadGallery();
                    }
                }
            })
            .catch(error => {
                console.error('Error refreshing data:', error);
            });
        }
        
        // Utility Functions
        function updateStats() {
            refreshData();
        }
        
        function showAlert(type, message) {
            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => {
                alert.remove();
            });
            
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            alert.style.display = 'block';
            
            // Insert after header
            const header = document.querySelector('.admin-header');
            header.insertAdjacentElement('afterend', alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
        
        // Logout function
        function logout() {
            if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
                const formData = new FormData();
                formData.append('action', 'logout');
                
                fetch('admin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'admin.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.location.href = 'admin.php';
                });
            }
        }
        
        // Initialize
        window.addEventListener('DOMContentLoaded', function() {
            loadExamples();
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
        
        // Auto refresh every 30 seconds
        setInterval(refreshData, 30000);
        
        // Add placeholder style
        const style = document.createElement('style');
        style.textContent = `
            .placeholder-message {
                text-align: center;
                padding: 60px 20px;
                color: #94a3b8;
            }
            .placeholder-message i {
                font-size: 3em;
                margin-bottom: 15px;
                display: block;
                opacity: 0.5;
            }
            .placeholder-message p {
                font-size: 1.1em;
                color: #64748b;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>