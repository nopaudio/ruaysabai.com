<?php
require_once 'config.php';

if (!isUserLoggedIn()) { header('Location: login.php?redirect=admin.php'); exit; }
$user = getCurrentUser();
if (!$user || $user['user_type'] !== 'admin') { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $db = Database::getInstance();
    $pm = new PromptManager();
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'get_data':
                jsonResponse(true, 'Data fetched', ['stats' => $pm->getStats(), 'gallery' => $db->select("SELECT * FROM gallery_items ORDER BY id DESC")]);
                break;
            
            case 'get_users':
                $page = max(1, (int)($_POST['page'] ?? 1)); $limit = 15; $offset = ($page - 1) * $limit;
                $search = isset($_POST['search']) ? '%' . $_POST['search'] . '%' : '%'; $filter = $_POST['filter'] ?? 'all';
                $where = " (username LIKE ? OR email LIKE ? OR full_name LIKE ?) "; $params = [$search, $search, $search];
                if ($filter !== 'all') { $where .= " AND member_type = ? "; $params[] = $filter; }
                $users = $db->select("SELECT id,username,full_name,email,member_type,points_balance,expire_date,status,avatar_url,created_at FROM users WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?", array_merge($params, [$limit, $offset]));
                $total = $db->select("SELECT COUNT(id) as c FROM users WHERE $where", $params)[0]['c'] ?? 0;
                jsonResponse(true, 'Users fetched', ['users' => $users, 'page' => $page, 'pages' => ceil($total / $limit)]);
                break;

            case 'update_user':
                $id = (int)($_POST['user_id'] ?? 0);
                if ($id <= 0) jsonResponse(false, 'ID ไม่ถูกต้อง');
                $data = ['member_type' => $_POST['member_type'], 'expire_date' => empty($_POST['expire_date']) ? null : $_POST['expire_date'], 'points_balance' => (int)$_POST['points_balance']];
                jsonResponse($db->update('users', $data, "id = ?", [$id]) !== false, 'อัปเดตข้อมูลสำเร็จ');
                break;

            case 'save_settings':
                $allowed = ['site_title', 'site_description', 'online_count', 'limit_guest', 'limit_free', 'limit_monthly'];
                foreach ($_POST['settings'] as $key => $value) if (in_array($key, $allowed)) $pm->setSetting($key, cleanInput($value));
                jsonResponse(true, 'บันทึกการตั้งค่าสำเร็จ');
                break;

            case 'save_gallery':
                // ตรวจสอบข้อมูลก่อนบันทึก
                if (empty(trim($_POST['title']))) jsonResponse(false, 'กรุณาใส่หัวข้อ');
                if (empty(trim($_POST['image_url']))) jsonResponse(false, 'ไม่พบ URL รูปภาพ');

                $data = ['title' => cleanInput($_POST['title']), 'image_url' => cleanInput($_POST['image_url']), 'prompt' => cleanInput($_POST['prompt']), 'icon' => 'fas fa-image'];
                $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
                $result = $id ? $db->update('gallery_items', $data, "id = ?", [$id]) : $db->insert('gallery_items', $data);
                jsonResponse($result !== false, $result ? 'บันทึกแกลเลอรี่สำเร็จ' : 'เกิดข้อผิดพลาดในการบันทึก');
                break;

            case 'delete_gallery':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) jsonResponse(false, 'ID ไม่ถูกต้อง');
                jsonResponse($db->execute("DELETE FROM gallery_items WHERE id = ?", [$id]), 'ลบแกลเลอรี่สำเร็จ');
                break;

            default: jsonResponse(false, 'Invalid action');
        }
    } catch (Exception $e) { jsonResponse(false, 'Error: ' . $e->getMessage()); }
    exit;
}

$pageData = getPageData();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
            <h1><i class="fas fa-user-shield"></i> Admin Panel</h1>
        </div>
        
        <div class="admin-nav">
            <button class="nav-btn active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
            <button class="nav-btn" data-section="users"><i class="fas fa-users-cog"></i> จัดการสมาชิก</button>
            <button class="nav-btn" data-section="gallery"><i class="fas fa-photo-video"></i> แกลเลอรี่</button>
            <button class="nav-btn" data-section="settings_general"><i class="fas fa-cogs"></i> ตั้งค่าทั่วไป</button>
        </div>
        
        <div id="alert-container"></div>

        <div id="dashboard" class="admin-section active"></div>
        
        <div id="users" class="admin-section">
            <div class="section-title"><span><i class="fas fa-users-cog"></i> จัดการสมาชิก</span></div>
            <div class="user-filters">
                <input type="text" id="user-search" placeholder="ค้นหา Username, Email, ชื่อ...">
                <select id="user-filter"><option value="all">ทั้งหมด</option><option value="free">ฟรี</option><option value="monthly">รายเดือน</option><option value="yearly">รายปี</option></select>
                <button class="btn" onClick="searchUsers(1)"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
            <table class="users-table"><thead><tr><th>ID</th><th>ผู้ใช้</th><th>ประเภท</th><th>แต้ม</th><th>หมดอายุ</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody id="users-tbody"></tbody></table>
            <div class="pagination" id="users-pagination"></div>
        </div>
        
        <div id="gallery" class="admin-section">
            <div class="section-title"><span><i class="fas fa-photo-video"></i> จัดการแกลเลอรี่</span><button class="btn" onClick="openGalleryModal()"><i class="fas fa-plus"></i> เพิ่มใหม่</button></div>
            <div class="items-grid" id="gallery-list"></div>
        </div>
        
        <div id="settings_general" class="admin-section">
            <div class="section-title"><span><i class="fas fa-cogs"></i> ตั้งค่าทั่วไป</span></div>
            <div class="form-group"><label>หัวข้อเว็บ</label><input type="text" id="site-title" value="<?php echo htmlspecialchars($pageData['settings']['site_title'] ?? ''); ?>"></div>
            <div class="form-group"><label>คำอธิบายเว็บ</label><textarea id="site-description"><?php echo htmlspecialchars($pageData['settings']['site_description'] ?? ''); ?></textarea></div>
            <div class="form-group"><label>ผู้ใช้ออนไลน์ (สมมติ)</label><input type="number" id="online-count" value="<?php echo htmlspecialchars($pageData['settings']['online_count'] ?? '100'); ?>"></div>
            <hr><h5 style="margin-bottom: 15px;">ตั้งค่าสิทธิ์การใช้งาน</h5>
            <div class="form-group"><label>สิทธิ์ Guest (ต่อวัน)</label><input type="number" id="limit-guest" value="<?php echo htmlspecialchars($pageData['settings']['limit_guest'] ?? '5'); ?>"></div>
            <div class="form-group"><label>สิทธิ์สมาชิกฟรี (ต่อวัน)</label><input type="number" id="limit-free" value="<?php echo htmlspecialchars($pageData['settings']['limit_free'] ?? '10'); ?>"></div>
            <div class="form-group"><label>สิทธิ์สมาชิกรายเดือน (ต่อเดือน)</label><input type="number" id="limit-monthly" value="<?php echo htmlspecialchars($pageData['settings']['limit_monthly'] ?? '60'); ?>"></div>
            <button class="btn btn-success" onClick="saveGeneralSettings()"><i class="fas fa-save"></i> บันทึก</button>
        </div>
    </div>
    
    <div id="user-modal" class="modal"></div>
    <div id="gallery-modal" class="modal"></div>
    
    <script>window.pageData = <?php echo json_encode($pageData); ?>;</script>
    <script src="admin_script.js"></script>
</body>
</html>