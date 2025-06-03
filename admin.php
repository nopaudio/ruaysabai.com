<?php
require_once 'config.php';

// session_start(); // ถูกเรียกใน config.php แล้ว ไม่ต้องเรียกซ้ำ

// ส่วน Login Admin
if ((!isset($_SESSION['admin_legacy_logged_in']) || $_SESSION['admin_legacy_logged_in'] !== true) && !isset($_POST['admin_password'])) {
    // ... (โค้ด HTML สำหรับฟอร์ม Login ทั้งหมด - เหมือนกับที่คุณให้มาล่าสุด) ...
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - AI Prompt Generator Pro</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            /* --- CSS เดิมสำหรับหน้า Login Admin --- */
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .login-container { background: rgba(255, 255, 255, 0.98); padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px; text-align: center; }
            .login-header { margin-bottom: 30px; }
            .login-header i { font-size: 3em; color: #667eea; margin-bottom: 20px; }
            .login-header h1 { color: #333; font-size: 1.8em; margin-bottom: 10px; }
            .form-group { margin-bottom: 20px; text-align: left; }
            .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
            .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 16px; transition: all 0.3s ease; }
            .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
            .login-btn { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s ease; }
            .login-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }
            .error-message { background: rgba(255, 107, 107, 0.1); border: 1px solid rgba(255, 107, 107, 0.3); color: #ff6b6b; padding: 10px; border-radius: 8px; margin-bottom: 20px; display: none; }
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
        <script> document.getElementById('error-message').style.display = 'block'; setTimeout(() => { document.getElementById('error-message').style.display = 'none'; }, 3000); </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_POST['admin_login']) && isset($_POST['admin_password'])) {
    if (adminLogin($_POST['admin_password'])) { // adminLogin จาก config.php จะตั้ง session
        // ไม่จำเป็นต้องตั้ง $_SESSION['admin_legacy_logged_in'] = true; ที่นี่อีก เพราะ adminLogin ทำแล้ว
        header('Location: admin.php');
        exit;
    } else {
        header('Location: admin.php?error=1');
        exit;
    }
}

if (!isset($_SESSION['admin_legacy_logged_in']) || $_SESSION['admin_legacy_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// --- START: AJAX Handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $promptManager = new PromptManager();
    // $db = Database::getInstance(); // $promptManager->db สามารถใช้ได้
    $action = $_POST['action'];
    
    // $adminUser = getCurrentUser(); // ถ้าจะใช้ระบบ login user ปกติสำหรับ admin
    // $adminId = $adminUser && $adminUser['user_type'] === 'admin' ? $adminUser['id'] : 1; // หรือ ID ของ Admin หลัก
    // สำหรับระบบ legacy admin password, เราอาจจะยังไม่มี $adminId ที่มาจาก user DB จริงๆ
    // อาจจะต้องกำหนดค่า default หรือหาวิธีอื่นถ้า action นั้นๆ ต้องการ admin ID
    $loggedInAdminId = 1; // สมมติ ID Admin หลักคือ 1 สำหรับ action ที่ต้องการ approved_by

    try {
        switch ($action) {
            case 'save_example':
                $title = cleanInput($_POST['title']);
                $prompt_text = cleanInput($_POST['prompt']);
                $icon = cleanInput($_POST['icon']) ?: 'fas fa-image';
                $id = isset($_POST['id']) ? intval($_POST['id']) : null;
                $data = ['title' => $title, 'prompt' => $prompt_text, 'icon' => $icon];
                if ($id) { $result = $promptManager->db->update('prompt_examples', $data, "id = ?", [$id]); } 
                else { $result = $promptManager->db->insert('prompt_examples', $data); }
                jsonResponse($result !== false, $result !== false ? 'บันทึก Example สำเร็จ' : 'พลาดในการบันทึก Example');
                break;
            case 'delete_example':
                $id = intval($_POST['id']);
                $result = $promptManager->db->delete('prompt_examples', "id = ?", [$id]);
                jsonResponse($result !== false, $result !== false ? 'ลบ Example สำเร็จ' : 'พลาดในการลบ Example');
                break;
            case 'save_gallery':
                $id = isset($_POST['id']) ? intval($_POST['id']) : null;
                $data = [
                    'title' => cleanInput($_POST['title']), 'description' => cleanInput($_POST['description']),
                    'image_url' => cleanInput($_POST['image_url']), 'prompt' => cleanInput($_POST['prompt']),
                    'icon' => cleanInput($_POST['icon']) ?: 'fas fa-image'
                ];
                if ($id) { $result = $promptManager->db->update('gallery_items', $data, "id = ?", [$id]); } 
                else { $result = $promptManager->db->insert('gallery_items', $data); }
                jsonResponse($result !== false, $result !== false ? 'บันทึก Gallery สำเร็จ' : 'พลาดในการบันทึก Gallery');
                break;
            case 'delete_gallery':
                $id = intval($_POST['id']);
                $result = $promptManager->db->delete('gallery_items', "id = ?", [$id]);
                jsonResponse($result !== false, $result !== false ? 'ลบ Gallery สำเร็จ' : 'พลาดในการลบ Gallery');
                break;
            case 'get_users':
                $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                $limit = 10; $offset = ($page - 1) * $limit;
                $search = isset($_POST['search']) ? cleanInput($_POST['search']) : '';
                $filter = isset($_POST['filter']) ? cleanInput($_POST['filter']) : '';
                $where_parts = []; $params = [];
                if ($search) { $where_parts[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)"; $searchTerm = "%{$search}%"; array_push($params, $searchTerm, $searchTerm, $searchTerm); }
                if ($filter && $filter !== 'all') { $where_parts[] = "member_type = ?"; $params[] = $filter; }
                $where_clause = count($where_parts) > 0 ? implode(' AND ', $where_parts) : "1=1";
                $totalResult = $promptManager->db->select("SELECT COUNT(*) as count FROM users WHERE {$where_clause}", $params);
                $total = $totalResult[0]['count'] ?? 0;
                $users = $promptManager->db->select("SELECT id, username, email, full_name, avatar_url, member_type, expire_date, points_balance, created_at, status FROM users WHERE {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$limit, $offset]));
                jsonResponse(true, 'ดึงข้อมูลผู้ใช้สำเร็จ', ['users' => $users, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
                break;
            case 'get_user':
                $user_id = intval($_POST['user_id']);
                $user = $promptManager->db->select("SELECT id, username, email, full_name, member_type, expire_date, points_balance, status FROM users WHERE id = ?", [$user_id]);
                jsonResponse(!empty($user), !empty($user) ? 'ดึงข้อมูลผู้ใช้สำเร็จ' : 'ไม่พบผู้ใช้', $user[0] ?? null);
                break;
            case 'update_user_membership':
                $user_id = intval($_POST['user_id']);
                $member_type = cleanInput($_POST['member_type']);
                $expire_date = !empty($_POST['expire_date']) ? cleanInput($_POST['expire_date']) : null;
                $points_balance = filter_input(INPUT_POST, 'points_balance', FILTER_VALIDATE_INT);
                $updateData = ['member_type' => $member_type, 'expire_date' => $expire_date, 'updated_at' => date('Y-m-d H:i:s')];
                if ($points_balance !== false && $points_balance !== null) { $updateData['points_balance'] = $points_balance; }
                if ($member_type === 'monthly') { $updateData['credits'] = 60; $updateData['daily_limit'] = 60; $updateData['user_type'] = 'premium'; }
                elseif ($member_type === 'yearly') { $updateData['credits'] = 999999; $updateData['daily_limit'] = 999999; $updateData['user_type'] = 'premium'; }
                else { $updateData['credits'] = 10; $updateData['daily_limit'] = 10; $updateData['user_type'] = 'free'; }
                $result = $promptManager->db->update('users', $updateData, "id = ?", [$user_id]);
                jsonResponse($result !== false, $result !== false ? 'อัปเดตสมาชิกสำเร็จ' : 'พลาดในการอัปเดตสมาชิก');
                break;
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                if ($user_id === 1) { jsonResponse(false, 'ไม่สามารถลบบัญชี Admin หลักได้'); break; }
                $result = $promptManager->db->update('users', ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')], "id = ?", [$user_id]);
                jsonResponse($result !== false, $result !== false ? 'ลบผู้ใช้สำเร็จ (สถานะ deleted)' : 'พลาดในการลบผู้ใช้');
                break;
            case 'get_pending_sellable_prompts':
                $page = isset($_POST['page']) ? intval($_POST['page']) : 1; $limit = 10; $offset = ($page - 1) * $limit;
                $status_filter = isset($_POST['status_filter']) ? cleanInput($_POST['status_filter']) : 'pending_approval';
                $where_clause_sp = "1=1"; $params_filter_sp = [];
                if ($status_filter !== 'all') { $where_clause_sp .= " AND sp.status = ?"; $params_filter_sp[] = $status_filter; }
                $totalResult_sp = $promptManager->db->select("SELECT COUNT(*) as count FROM sellable_prompts sp WHERE " . $where_clause_sp, $params_filter_sp);
                $total_sp = $totalResult_sp[0]['count'] ?? 0;
                $final_params_sp = array_merge($params_filter_sp, [$limit, $offset]);
                $prompts = $promptManager->db->select("SELECT sp.*, u.username as seller_username FROM sellable_prompts sp JOIN users u ON sp.user_id = u.id WHERE " . $where_clause_sp . " ORDER BY sp.created_at ASC LIMIT ? OFFSET ?", $final_params_sp);
                jsonResponse(true, 'ดึงข้อมูล Prompt สำเร็จ', ['prompts' => $prompts, 'total' => $total_sp, 'page' => $page, 'pages' => ceil($total_sp / $limit)]);
                break;
            case 'approve_sellable_prompt':
                $prompt_id = intval($_POST['prompt_id']);
                $updateData = ['status' => 'approved', 'approved_by' => $loggedInAdminId, 'approved_at' => date('Y-m-d H:i:s')];
                $result = $promptManager->db->update('sellable_prompts', $updateData, "id = ?", [$prompt_id]);
                jsonResponse($result !== false, $result !== false ? 'อนุมัติ Prompt สำเร็จ' : 'พลาดในการอนุมัติ Prompt');
                break;
            case 'reject_sellable_prompt':
                $prompt_id = intval($_POST['prompt_id']);
                $new_status = isset($_POST['new_status']) && in_array($_POST['new_status'], ['rejected', 'pending_approval']) ? $_POST['new_status'] : 'rejected';
                $updateData = ['status' => $new_status, 'approved_by' => $loggedInAdminId, 'approved_at' => date('Y-m-d H:i:s')];
                $result = $promptManager->db->update('sellable_prompts', $updateData, "id = ?", [$prompt_id]);
                $message = $new_status === 'rejected' ? 'ปฏิเสธ Prompt สำเร็จ' : 'เปลี่ยนสถานะ Prompt สำเร็จ';
                jsonResponse($result !== false, $result !== false ? $message : 'พลาดในการเปลี่ยนสถานะ');
                break;
            case 'update_marketplace_settings':
                $all_ok_mp = true; 
                $allowed_keys_mp = ['commission_rate', 'default_prompt_price'];
                if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                    foreach ($_POST['settings'] as $key => $value) {
                        if (in_array($key, $allowed_keys_mp)) { 
                            $result_mp_set = $promptManager->setSetting($key, cleanInput($value));
                            if ($result_mp_set === false) { // ตรวจสอบ === false โดยตรง
                                $all_ok_mp = false; 
                                error_log("Admin Action: Failed to set marketplace setting for key: " . $key . " | Value: " . $value);
                                break; 
                            }
                        }
                    }
                } else {
                    $all_ok_mp = false; // ไม่มี settings ส่งมา
                     error_log("Admin Action: No settings array provided for update_marketplace_settings.");
                }
                jsonResponse($all_ok_mp, $all_ok_mp ? 'บันทึกตั้งค่า Marketplace สำเร็จ' : 'พลาดในการบันทึกตั้งค่า Marketplace');
                break;
            case 'save_settings': // สำหรับตั้งค่าทั่วไปและข้อความเว็บ
                $all_ok_gen = true; 
                $allowed_general_keys = ['site_title', 'site_description', 'online_count', 'placeholder_title', 'placeholder_description', 'gallery_title', 'gallery_description'];
                if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                    foreach ($_POST['settings'] as $key => $value) {
                        if (in_array($key, $allowed_general_keys)) { 
                            $result_gen_set = $promptManager->setSetting($key, cleanInput($value));
                            if ($result_gen_set === false) { // ตรวจสอบ === false โดยตรง
                                $all_ok_gen = false; 
                                error_log("Admin Action: Failed to set general setting for key: " . $key . " | Value: " . $value);
                                break; 
                            }
                        }
                    }
                } else {
                     $all_ok_gen = false; // ไม่มี settings ส่งมา
                     error_log("Admin Action: No settings array provided for save_settings.");
                }
                jsonResponse($all_ok_gen, $all_ok_gen ? 'บันทึกตั้งค่าทั่วไปสำเร็จ' : 'พลาดในการบันทึกตั้งค่าทั่วไป');
                break;
            case 'get_data':
                $pageDataArray = getPageData(); $pageDataArray['stats'] = $promptManager->getStats();
                jsonResponse(true, 'ดึงข้อมูลสำเร็จ', $pageDataArray);
                break;
            case 'logout':
                adminLogout(); jsonResponse(true, 'ออกจากระบบสำเร็จ');
                break;
            default:
                jsonResponse(false, 'Invalid action: ' . htmlspecialchars($action));
        }
    } catch (Exception $e) {
        error_log('Admin action error: ' . $e->getMessage() . ' on action: ' . $action);
        jsonResponse(false, 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage());
    }
    exit;
}
// --- END: AJAX Handler ---


// ดึงข้อมูลเริ่มต้น
$promptManager = new PromptManager();
$pageData = getPageData();
$pageData['stats'] = $promptManager->getStats();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Backend - <?php echo htmlspecialchars($pageData['settings']['site_title'] ?? SITE_TITLE); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ... (CSS ทั้งหมดจากไฟล์ที่คุณให้มาล่าสุด) ... */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); min-height: 100vh; color: #374151; } /* ปรับสีพื้นหลัง */
        .admin-container { max-width: 1600px; margin: 0 auto; padding: 20px;}
        .admin-header { background: white; padding: 25px 30px; border-radius: 12px; text-align: center; margin-bottom: 25px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); position: relative; }
        .admin-header h1 { color: #4f46e5; font-size: 2.2em; font-weight: 700; margin-bottom: 8px; }
        .admin-header p { color: #6b7280; font-size: 1em; }
        .logout-btn { position: absolute; top: 25px; right: 25px; background: #ef4444; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; transition: background-color 0.3s ease; }
        .logout-btn:hover { background: #dc2626; }
        .admin-nav { display: flex; gap: 10px; margin-bottom: 25px; justify-content: flex-start; flex-wrap: wrap; padding: 10px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .nav-btn { background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s ease-in-out; color: #4b5563; font-size: 0.9em; display: flex; align-items: center; gap: 8px; }
        .nav-btn.active, .nav-btn:hover { background: #4f46e5; color: white; border-color: #4f46e5; transform: translateY(-1px); box-shadow: 0 6px 12px rgba(79, 70, 229, 0.2); }
        .admin-section { display: none; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); margin-bottom: 25px; }
        .admin-section.active { display: block; animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .section-title { font-size: 1.6em; font-weight: 600; color: #374151; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;}
        .section-title .btn { padding: 10px 18px; font-size: 0.9em; margin-left:auto;}
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #4b5563; font-size: 0.9em;}
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95em; transition: border-color 0.2s ease, box-shadow 0.2s ease; background: #f9fafb; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15); background: white; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .btn { background: #4f46e5; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background-color 0.3s ease, transform 0.2s ease; font-size: 0.95em; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { background: #4338ca; transform: translateY(-1px); }
        .btn-danger { background: #ef4444; } .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; } .btn-success:hover { background: #059669; }
        .btn-warning { background: #f59e0b; color:white;} .btn-warning:hover { background: #d97706; }
        .items-grid { display: grid; gap: 18px; margin-top: 20px; }
        .item-card { background: #f9fafb; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb; transition: box-shadow 0.3s ease; }
        .item-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.07); }
        .item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 10px; }
        .item-title { font-weight: 500; color: #1f2937; flex: 1; font-size: 1.05em; }
        .item-actions { display: flex; gap: 8px; }
        .item-actions .btn { padding: 8px 12px; font-size:0.85em;}
        .item-content { background: white; padding: 12px; border-radius: 6px; font-family: 'SF Mono', Monaco, monospace; font-size: 0.85em; line-height: 1.6; color: #4b5563; border: 1px solid #e5e7eb; margin-bottom: 12px; max-height: 100px; overflow-y: auto; }
        .gallery-item-card { display: grid; grid-template-columns: 150px 1fr; gap: 15px; align-items: flex-start; } 
        .gallery-image-preview { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 15px; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 25px; border-radius: 12px; max-width: 550px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { font-size: 1.4em; font-weight: 600; color: #4f46e5; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .close-modal { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.4em; cursor: pointer; color: #9ca3af; transition: color 0.2s ease; }
        .close-modal:hover { color: #374151; }
        .image-preview { width: 100%; max-width: 180px; height: auto; border-radius: 8px; margin-bottom: 12px; border: 1px solid #e5e7eb; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .stat-card { background: #f9fafb; border: 1px solid #e5e7eb; padding: 20px; border-radius: 10px; text-align: center; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.06); }
        .stat-number { font-size: 2.2em; font-weight: 700; color: #4f46e5; display: block; }
        .stat-label { color: #6b7280; font-weight: 500; margin-top: 4px; font-size: 0.9em; }
        .alert { padding: 12px 18px; border-radius: 8px; margin: 0 0 20px 0; display: none; animation: slideDown 0.3s ease-out; font-size: 0.95em; position:fixed; top: 80px; right: 20px; z-index: 1001; min-width: 300px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);}
        @keyframes slideDown { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
        .alert.success { background: #dcfce7; border: 1px solid #86efac; color: #15803d; }
        .alert.error { background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; }
        .user-filters { display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
        .user-filters input, .user-filters select { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9em; background: #f9fafb;}
        .user-filters input { min-width: 220px; flex: 1; }
        .users-table { width: 100%; border-collapse: collapse; margin-top: 18px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); font-size: 0.9em;}
        .users-table th, .users-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #f3f4f6; }
        .users-table th { background: #f9fafb; color: #4b5563; font-weight: 500; text-transform: uppercase; font-size:0.8em; letter-spacing: 0.5px;}
        .users-table tr:hover { background: #f3f4f6; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .member-badge { padding: 3px 7px; border-radius: 10px; font-size: 0.75em; font-weight: 500; text-transform: uppercase; }
        .member-badge.free { background: #e5e7eb; color: #4b5563; } .member-badge.monthly { background: #dbeafe; color: #2563eb; } .member-badge.yearly { background: #fef3c7; color: #d97706; }
        .status-badge { padding: 3px 7px; border-radius: 10px; font-size: 0.75em; font-weight: 500; text-transform: capitalize;}
        .status-badge.active { background: #dcfce7; color: #16a34a;}
        .status-badge.suspended { background: #fef3c7; color: #d97706;}
        .status-badge.deleted { background: #fee2e2; color: #dc2626;}
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 18px; }
        .pagination button { padding: 7px 10px; border: 1px solid #e5e7eb; background: white; border-radius: 5px; cursor: pointer; transition: all 0.2s ease; font-size: 0.85em;}
        .pagination button:hover, .pagination button.active { background: #4f46e5; color: white; border-color: #4f46e5; }
        .pagination button:disabled { opacity: 0.6; cursor: not-allowed; }
        .placeholder-message { text-align: center; padding: 50px 20px; color: #9ca3af; }
        .placeholder-message i { font-size: 2.5em; margin-bottom: 12px; display: block; opacity: 0.6; }
        .placeholder-message p { font-size: 1em; color: #6b7280; }
        #marketplace-prompts-table th, #marketplace-prompts-table td { vertical-align: middle; }
        #marketplace-prompts-table .prompt-image-admin { max-width: 100px; max-height: 70px; border-radius: 5px; object-fit: cover; }
        #marketplace-prompts-table .prompt-text-admin { max-height: 60px; overflow-y: auto; font-size: 0.8em; background-color: #f8f9fa; padding: 5px; border-radius: 3px; border: 1px solid #e9ecef; white-space: pre-wrap; word-break: break-all;}
        #marketplace-prompts-table .badge-status { padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
        #marketplace-prompts-table .badge-status.pending_approval { background-color: #ffc107; color: #212529;}
        #marketplace-prompts-table .badge-status.approved { background-color: #28a745;}
        #marketplace-prompts-table .badge-status.rejected { background-color: #dc3545;}
        #settings_marketplace .form-group { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        #settings_marketplace .form-group:last-child { border-bottom: none; }
        @media (max-width: 768px) { /* Additional responsive fixes for smaller tables */
            .admin-container { padding: 10px; } .admin-header { padding: 20px; } .admin-header h1 { font-size: 1.8em; }
            .logout-btn { position: static; margin-top: 15px; } .admin-nav { flex-direction: column; }
            .nav-btn { justify-content: center; padding: 10px 15px; font-size: 0.95em; } .admin-section { padding: 15px; }
            .gallery-item-card { grid-template-columns: 1fr; gap: 12px; } .modal-content { padding: 20px; margin: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .users-table, #marketplace-prompts-table { font-size: 0.85em; } 
            .users-table th, .users-table td, #marketplace-prompts-table th, #marketplace-prompts-table td { padding: 8px 6px; word-break: break-word; }
            .user-filters { flex-direction: column; align-items: stretch; } .user-filters input { min-width: auto; }
            #marketplace-prompts-table .prompt-image-admin { max-width: 70px; max-height: 50px; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </button>
            <h1><i class="fas fa-user-shield"></i> Admin Panel</h1>
            <p>จัดการเนื้อหาและการตั้งค่าของ <?php echo htmlspecialchars($pageData['settings']['site_title'] ?? SITE_TITLE); ?></p>
        </div>
        
        <div class="admin-nav">
            <button class="nav-btn active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</button>
            <button class="nav-btn" data-section="users"><i class="fas fa-users-cog"></i> จัดการสมาชิก</button>
            <button class="nav-btn" data-section="marketplace_prompts"><i class="fas fa-store"></i> อนุมัติ Prompt ขาย</button>
            <button class="nav-btn" data-section="examples"><i class="fas fa-lightbulb"></i> Prompt ยอดนิยม</button>
            <button class="nav-btn" data-section="gallery"><i class="fas fa-photo-video"></i> แกลเลอรี่</button>
            <button class="nav-btn" data-section="messages"><i class="fas fa-envelope-open-text"></i> ข้อความเว็บ</button>
            <button class="nav-btn" data-section="settings_general"><i class="fas fa-cogs"></i> ตั้งค่าทั่วไป</button>
            <button class="nav-btn" data-section="settings_marketplace"><i class="fas fa-sliders-h"></i> ตั้งค่า Marketplace</button>
        </div>
        
        <div id="alert-container"></div>


        <div id="dashboard" class="admin-section active">
             <div class="section-title"><span><i class="fas fa-tachometer-alt"></i> Dashboard</span></div>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-number" id="total-examples"><?php echo htmlspecialchars($pageData['stats']['total_examples']); ?></span><div class="stat-label">Prompt ยอดนิยม</div></div>
                <div class="stat-card"><span class="stat-number" id="total-gallery"><?php echo htmlspecialchars($pageData['stats']['total_gallery']); ?></span><div class="stat-label">รายการแกลเลอรี่</div></div>
                <div class="stat-card"><span class="stat-number" id="total-user-prompts"><?php echo htmlspecialchars($pageData['stats']['total_user_prompts']); ?></span><div class="stat-label">Prompt ที่ผู้ใช้สร้าง</div></div>
                <div class="stat-card"><span class="stat-number" id="today-prompts"><?php echo htmlspecialchars($pageData['stats']['today_prompts']); ?></span><div class="stat-label">Prompt วันนี้</div></div>
                <div class="stat-card"><span class="stat-number" id="stat-sellable-prompts"><?php echo htmlspecialchars($pageData['stats']['total_sellable_prompts'] ?? 0); ?></span><div class="stat-label">Prompt ที่ขาย (อนุมัติแล้ว)</div></div>
                <div class="stat-card"><span class="stat-number" id="stat-transactions"><?php echo htmlspecialchars($pageData['stats']['total_transactions'] ?? 0); ?></span><div class="stat-label">จำนวนการซื้อขาย</div></div>
            </div>
        </div>
        
        <div id="users" class="admin-section">
            <div class="section-title"><span><i class="fas fa-users-cog"></i> จัดการสมาชิก</span></div>
            <div class="user-filters">
                <input type="text" id="user-search" placeholder="ค้นหาสมาชิก (username, email, ชื่อ)">
                <select id="user-filter">
                    <option value="all">ทุกประเภท</option> <option value="free">ฟรี</option> <option value="monthly">รายเดือน</option> <option value="yearly">รายปี</option>
                </select>
                <button class="btn" onclick="searchUsers()"><i class="fas fa-search"></i> ค้นหา</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="users-table" id="users-table">
                    <thead><tr><th>ID</th><th>Avatar</th><th>Username</th><th>ชื่อ-สกุล</th><th>Email</th><th>ประเภท</th><th>แต้ม</th><th>วันหมดอายุ</th><th>สถานะ</th><th>สร้างเมื่อ</th><th>จัดการ</th></tr></thead>
                    <tbody id="users-tbody"></tbody>
                </table>
            </div>
            <div class="pagination" id="users-pagination"></div>
        </div>

        <div id="marketplace_prompts" class="admin-section">
            <div class="section-title"><span><i class="fas fa-store"></i> จัดการ Prompt ที่ส่งมาขาย</span></div>
             <div class="user-filters">
                <select id="marketplace-prompt-filter" onchange="loadPendingSellablePrompts(1)">
                    <option value="pending_approval">รออนุมัติ</option>
                    <option value="approved">อนุมัติแล้ว</option>
                    <option value="rejected">ถูกปฏิเสธ</option>
                    <option value="all">ทั้งหมด</option>
                </select>
            </div>
            <div style="overflow-x: auto;">
                <table class="users-table" id="marketplace-prompts-table"> <thead>
                        <tr>
                            <th>ID</th>
                            <th>ผู้ขาย</th>
                            <th>หัวข้อ</th>
                            <th>รูปภาพ</th>
                            <th>Prompt จริง (ตัวอย่าง)</th>
                            <th>ราคา (แต้ม)</th>
                            <th>Tags</th>
                            <th>สถานะ</th>
                            <th>วันที่ส่ง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="marketplace-prompts-tbody">
                        </tbody>
                </table>
            </div>
            <div class="pagination" id="marketplace-prompts-pagination"></div>
        </div>
        <div id="examples" class="admin-section">
            <div class="section-title"><span><i class="fas fa-lightbulb"></i> จัดการ Prompt ยอดนิยม</span><button class="btn" onclick="openExampleModal()"><i class="fas fa-plus"></i> เพิ่มใหม่</button></div>
            <div class="items-grid" id="examples-list"></div>
        </div>
        
        <div id="gallery" class="admin-section">
            <div class="section-title"><span><i class="fas fa-photo-video"></i> จัดการแกลเลอรี่</span><button class="btn" onclick="openGalleryModal()"><i class="fas fa-plus"></i> เพิ่มใหม่</button></div>
            <div class="items-grid" id="gallery-list"></div>
        </div>
        
        <div id="messages" class="admin-section">
            <div class="section-title"><span><i class="fas fa-envelope-open-text"></i> จัดการข้อความแสดงผลทั่วไป</span></div>
            <div class="form-group"><label>ข้อความส่วน "เริ่มสร้าง Prompt ของคุณ" (Title)</label><input type="text" id="placeholder-title" class="form-control" value="<?php echo htmlspecialchars($pageData['settings']['placeholder_title'] ?? ''); ?>"></div>
            <div class="form-group"><label>คำอธิบาย</label><textarea id="placeholder-description" class="form-control"><?php echo htmlspecialchars($pageData['settings']['placeholder_description'] ?? ''); ?></textarea></div>
            <div class="form-group"><label>ข้อความหัวข้อแกลเลอรี่ (Title)</label><input type="text" id="gallery-title-input-setting" class="form-control" value="<?php echo htmlspecialchars($pageData['settings']['gallery_title'] ?? ''); ?>"></div>
            <div class="form-group"><label>คำอธิบายแกลเลอรี่</label><textarea id="gallery-description-setting" class="form-control"><?php echo htmlspecialchars($pageData['settings']['gallery_description'] ?? ''); ?></textarea></div>
            <button class="btn btn-success" onclick="saveGeneralMessages()"><i class="fas fa-save"></i> บันทึกข้อความ</button>
        </div>
        
        <div id="settings_general" class="admin-section">
            <div class="section-title"><span><i class="fas fa-cogs"></i> ตั้งค่าทั่วไป</span></div>
            <div class="form-group"><label>จำนวนผู้ใช้ออนไลน์ (ตัวเลขสมมติสำหรับแสดงผล)</label><input type="number" id="online-count" class="form-control" value="<?php echo htmlspecialchars($pageData['settings']['online_count'] ?? '100'); ?>" min="1"></div>
            <div class="form-group"><label>หัวข้อหลักของเว็บไซต์ (Site Title)</label><input type="text" id="site-title" class="form-control" value="<?php echo htmlspecialchars($pageData['settings']['site_title'] ?? SITE_TITLE); ?>"></div>
            <div class="form-group"><label>คำอธิบายย่อของเว็บไซต์ (Site Description)</label><textarea id="site-description" class="form-control"><?php echo htmlspecialchars($pageData['settings']['site_description'] ?? ''); ?></textarea></div>
            <button class="btn btn-success" onclick="saveGeneralSettings()"><i class="fas fa-save"></i> บันทึกการตั้งค่าทั่วไป</button>
        </div>

        <div id="settings_marketplace" class="admin-section">
            <div class="section-title"><span><i class="fas fa-sliders-h"></i> ตั้งค่า Marketplace</span></div>
            <div class="form-group">
                <label for="commission-rate">อัตราค่าบริการ (%)</label>
                <input type="number" id="commission-rate" class="form-control" value="<?php echo htmlspecialchars($promptManager->getSetting('commission_rate', 5)); ?>" min="0" max="100" step="0.1">
                <small>เมื่อมีการขาย Prompt ระบบจะหักค่าบริการตาม % นี้จากผู้ขาย</small>
            </div>
            <div class="form-group">
                <label for="default-prompt-price">ราคา Prompt เริ่มต้น (แต้ม)</label>
                <input type="number" id="default-prompt-price" class="form-control" value="<?php echo htmlspecialchars($promptManager->getSetting('default_prompt_price', 10)); ?>" min="1">
                <small>ราคาเริ่มต้นที่แนะนำเมื่อสมาชิกโพสต์ขาย Prompt</small>
            </div>
            <button class="btn btn-success" onclick="saveMarketplaceSettings()">
                <i class="fas fa-save"></i> บันทึกการตั้งค่า Marketplace
            </button>
        </div>
        </div>
    
    <div id="user-modal" class="modal"> <div class="modal-content"> <button class="close-modal" onclick="closeModal('user-modal')">&times;</button> <div class="modal-header"><i class="fas fa-user-edit"></i> <span id="user-modal-title">แก้ไขข้อมูลสมาชิก</span></div> <div id="user-info-display" style="margin-bottom: 15px; padding: 12px; background: #f3f4f6; border-radius: 6px; font-size:0.9em;"></div> <div class="form-group"><label>ประเภทสมาชิก</label><select id="user-member-type" class="form-control" onchange="handleMemberTypeChange()"><option value="free">ฟรี</option><option value="monthly">รายเดือน</option><option value="yearly">รายปี</option></select></div> <div class="form-group" id="expire-date-group"><label>วันหมดอายุ</label><input type="date" id="user-expire-date" class="form-control"><small style="color: #6b7280; font-size:0.8em;">สำหรับสมาชิกรายเดือน/รายปี</small></div> <div class="form-group"><label>แต้มสะสม (Points)</label><input type="number" id="user-points-balance" class="form-control" min="0"></div> <button class="btn btn-success" onclick="saveUserMembership()"><i class="fas fa-save"></i> บันทึก</button> </div> </div>
    <div id="example-modal" class="modal"><div class="modal-content"><button class="close-modal" onclick="closeModal('example-modal')">&times;</button><div class="modal-header"><i class="fas fa-lightbulb"></i> <span id="example-modal-title">เพิ่ม Prompt</span></div><div class="form-group"><label>ชื่อ/หัวข้อ</label><input type="text" id="example-title" class="form-control"></div><div class="form-group"><label>Prompt</label><textarea id="example-prompt" class="form-control"></textarea></div><div class="form-group"><label>ไอคอน (Font Awesome)</label><input type="text" id="example-icon" class="form-control" value="fas fa-image"></div><button class="btn btn-success" onclick="saveExample()"><i class="fas fa-save"></i> บันทึก</button></div></div>
    <div id="gallery-modal" class="modal"><div class="modal-content"><button class="close-modal" onclick="closeModal('gallery-modal')">&times;</button><div class="modal-header"><i class="fas fa-photo-video"></i> <span id="gallery-modal-title">เพิ่มรายการแกลเลอรี่</span></div><div class="form-group"><label>ชื่อ/หัวข้อ</label><input type="text" id="gallery-title-input" class="form-control"></div><div class="form-group"><label>คำอธิบาย</label><input type="text" id="gallery-desc" class="form-control"></div><div class="form-group"><label>URL รูปภาพ</label><input type="url" id="gallery-image" class="form-control" onchange="previewImage(this.value, 'gallery-preview')"><img id="gallery-preview" class="image-preview" style="display: none;"></div><div class="form-group"><label>Prompt</label><textarea id="gallery-prompt" class="form-control"></textarea></div><div class="form-group"><label>ไอคอน (Font Awesome)</label><input type="text" id="gallery-icon" class="form-control" value="fas fa-image"></div><button class="btn btn-success" onclick="saveGalleryItem()"><i class="fas fa-save"></i> บันทึก</button></div></div>
    
    <div id="marketplace-prompt-detail-modal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <button class="close-modal" onclick="closeModal('marketplace-prompt-detail-modal')">&times;</button>
            <div class="modal-header"><i class="fas fa-store-alt"></i> รายละเอียด Prompt ขาย</div>
            <div id="marketplace-prompt-detail-content" style="font-size:0.9em;"></div>
            <div id="marketplace-prompt-actions" style="margin-top: 20px; text-align: right; display:flex; gap:10px; justify-content:flex-end;"></div>
        </div>
    </div>

    <script>
        // Data from PHP
        let examplesData = <?php echo json_encode($pageData['examples']); ?>;
        let galleryData = <?php echo json_encode($pageData['gallery']); ?>;
        let currentEditingExample = null;
        let currentEditingGallery = null;
        let currentEditingUser = null;
        let currentMarketplacePromptsPage = 1;
        let currentUsersPage = 1;
        const avatarPlaceholder = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%2240%22%20height%3D%2240%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2040%2040%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_179_svg%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_179_svg%22%3E%3Crect%20width%3D%2240%22%20height%3D%2240%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2212.5%22%20y%3D%2224.5%22%3EN/A%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';


        function htmlspecialchars(str) {
            if (typeof str !== 'string' && typeof str !== 'number') return '';
            str = String(str);
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function showSection(sectionId, event = null) {
            document.querySelectorAll('.admin-section').forEach(section => section.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
            
            const sectionElement = document.getElementById(sectionId);
            if (sectionElement) {
                sectionElement.classList.add('active');
            } else {
                console.error("Section not found: ", sectionId, ". Defaulting to dashboard.");
                document.getElementById('dashboard').classList.add('active');
                const defaultNavButton = document.querySelector('.nav-btn[data-section="dashboard"]');
                if(defaultNavButton) defaultNavButton.classList.add('active');
                return;
            }
            
            let clickedButton;
            if (event && event.target) { // Check if event and event.target exist
                clickedButton = event.target.closest('.nav-btn');
            } else { // Fallback for programmatic calls or if event is null
                clickedButton = document.querySelector(`.nav-btn[data-section="${sectionId}"]`);
            }

            if (clickedButton) {
                clickedButton.classList.add('active');
            } else { // Fallback if no button found by data-section (e.g., initial load)
                 const firstNavButton = document.querySelector('.admin-nav .nav-btn');
                 if(firstNavButton) firstNavButton.classList.add('active');
            }


            if (sectionId === 'examples') loadExamples();
            else if (sectionId === 'gallery') loadGallery();
            else if (sectionId === 'dashboard') updateStats();
            else if (sectionId === 'users') loadUsers(currentUsersPage);
            else if (sectionId === 'marketplace_prompts') loadPendingSellablePrompts(currentMarketplacePromptsPage);
            else if (sectionId === 'settings_marketplace') loadMarketplaceSettings();
        }
        
        // --- START: Marketplace Admin JS ---
        function loadPendingSellablePrompts(page = 1) {
            currentMarketplacePromptsPage = page;
            const filterStatus = document.getElementById('marketplace-prompt-filter').value;
            const formData = new FormData();
            formData.append('action', 'get_pending_sellable_prompts');
            formData.append('page', page);
            formData.append('status_filter', filterStatus);

            fetch('admin.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMarketplacePromptsTable(data.data.prompts);
                    renderMarketplacePromptsPagination(data.data.page, data.data.pages);
                } else {
                    showAlert('error', data.message || 'ไม่สามารถโหลดข้อมูล Prompt ได้');
                    document.getElementById('marketplace-prompts-tbody').innerHTML = '<tr><td colspan="10" class="placeholder-message"><i class="fas fa-exclamation-triangle"></i><p>'+(data.message || 'ไม่สามารถโหลดข้อมูลได้')+'</p></td></tr>';
                }
            })
            .catch(error => {
                showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Marketplace Prompts)');
                console.error('Error loading marketplace prompts:', error);
                document.getElementById('marketplace-prompts-tbody').innerHTML = '<tr><td colspan="10" class="placeholder-message"><i class="fas fa-exclamation-triangle"></i><p>เกิดข้อผิดพลาดในการเชื่อมต่อ</p></td></tr>';
            });
        }

        function renderMarketplacePromptsTable(prompts) {
            const tbody = document.getElementById('marketplace-prompts-tbody');
            tbody.innerHTML = '';
            if (!prompts || prompts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="placeholder-message"><i class="fas fa-inbox"></i><p>ไม่พบ Prompt ที่ตรงเงื่อนไข</p></td></tr>';
                return;
            }
            prompts.forEach(prompt => {
                const row = document.createElement('tr');
                row.setAttribute('data-prompt-id', prompt.id); // Store prompt id for easy access
                const actualPromptShort = (prompt.actual_prompt && prompt.actual_prompt.length > 100) ? prompt.actual_prompt.substring(0, 100) + '...' : prompt.actual_prompt;
                const createdDate = new Date(prompt.created_at).toLocaleDateString('th-TH', { year: '2-digit', month: 'short', day: 'numeric'});

                row.innerHTML = `
                    <td>${prompt.id}</td>
                    <td>${htmlspecialchars(prompt.seller_username || 'N/A')}</td>
                    <td>${htmlspecialchars(prompt.title)}</td>
                    <td>${prompt.image_url ? `<img src="${htmlspecialchars(prompt.image_url)}" class="prompt-image-admin" onerror="this.style.display='none'; this.alt='Error';">` : '-'}</td>
                    <td style="max-width: 200px;"><div class="prompt-text-admin" title="${htmlspecialchars(prompt.actual_prompt)}">${htmlspecialchars(actualPromptShort)}</div></td>
                    <td>${prompt.price_points} <i class="fas fa-coins" style="color: #f59e0b;"></i></td>
                    <td>${htmlspecialchars(prompt.tags) || '-'}</td>
                    <td><span class="badge-status ${prompt.status}">${htmlspecialchars(prompt.status.replace('_', ' '))}</span></td>
                    <td>${createdDate}</td>
                    <td class="item-actions">
                        <button class="btn btn-sm" onclick="viewMarketplacePromptDetail(${prompt.id})" style="background-color:#3b82f6;"><i class="fas fa-eye"></i></button>
                        ${prompt.status === 'pending_approval' ? `
                            <button class="btn btn-sm btn-success" onclick="approveSellablePrompt(${prompt.id}, this)"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="rejectSellablePrompt(${prompt.id}, this)"><i class="fas fa-times"></i></button>
                        ` : ''}
                         ${prompt.status === 'approved' ? `
                            <button class="btn btn-sm btn-warning" onclick="rejectSellablePrompt(${prompt.id}, this, 'rejected')"><i class="fas fa-ban"></i></button>
                        ` : ''}
                         ${prompt.status === 'rejected' ? `
                             <button class="btn btn-sm btn-success" onclick="approveSellablePrompt(${prompt.id}, this)"><i class="fas fa-redo"></i></button>
                        ` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function viewMarketplacePromptDetail(promptId) {
            const allPrompts = Array.from(document.querySelectorAll('#marketplace-prompts-tbody tr')).map(row => {
                const statusBadge = row.cells[7].querySelector('.badge-status');
                return {
                    id: row.dataset.promptId,
                    seller_username: row.cells[1].innerText,
                    title: row.cells[2].innerText,
                    image_url_html: row.cells[3].innerHTML, 
                    actual_prompt: row.querySelector('.prompt-text-admin') ? row.querySelector('.prompt-text-admin').title : '',
                    price_points: row.cells[5].innerText,
                    tags: row.cells[6].innerText,
                    status_text: statusBadge ? statusBadge.innerText : 'N/A',
                    status_class: statusBadge ? statusBadge.className.split(' ').find(cls => cls !== 'badge-status') : 'unknown',
                    created_at_display: row.cells[8].innerText
                };
            });

            const prompt = allPrompts.find(p => p.id == promptId);

            if (!prompt) {
                showAlert('error', 'ไม่พบข้อมูล Prompt หรือข้อมูลไม่สมบูรณ์');
                console.log("Prompt not found in current view for ID: ", promptId, "Available prompts: ", allPrompts);
                return;
            }

            const detailContent = document.getElementById('marketplace-prompt-detail-content');
            detailContent.innerHTML = `
                <p><strong>ID:</strong> ${prompt.id}</p>
                <p><strong>หัวข้อ:</strong> ${htmlspecialchars(prompt.title)}</p>
                <p><strong>ผู้ขาย:</strong> ${htmlspecialchars(prompt.seller_username)}</p>
                <p><strong>ราคา:</strong> ${htmlspecialchars(prompt.price_points)}</p>
                <p><strong>Tags:</strong> ${htmlspecialchars(prompt.tags)}</p>
                <p><strong>สถานะ:</strong> <span class="badge-status ${prompt.status_class}">${htmlspecialchars(prompt.status_text)}</span></p>
                <p><strong>วันที่ส่ง:</strong> ${htmlspecialchars(prompt.created_at_display)}</p>
                <p><strong>รูปภาพตัวอย่าง:</strong></p>
                <div style="max-width: 300px; margin-bottom: 10px;">${prompt.image_url_html.includes('img') ? prompt.image_url_html.replace('prompt-image-admin', 'image-preview') : 'ไม่มีรูปภาพ'}</div>
                <p><strong>Prompt จริง:</strong></p>
                <div class="item-content" style="max-height: 200px; background: #f9fafb; border: 1px solid #e5e7eb; white-space: pre-wrap; word-wrap: break-word;">${htmlspecialchars(prompt.actual_prompt)}</div>
            `;

            const actionsContainer = document.getElementById('marketplace-prompt-actions');
            actionsContainer.innerHTML = ''; 

            if (prompt.status_class === 'pending_approval') {
                actionsContainer.innerHTML += `<button class="btn btn-success" onclick="approveSellablePrompt(${prompt.id}, null, true)"><i class="fas fa-check"></i> อนุมัติ</button>`;
                actionsContainer.innerHTML += `<button class="btn btn-danger" onclick="rejectSellablePrompt(${prompt.id}, null, 'rejected', true)"><i class="fas fa-times"></i> ปฏิเสธ</button>`;
            } else if (prompt.status_class === 'approved') {
                actionsContainer.innerHTML += `<button class="btn btn-warning" onclick="rejectSellablePrompt(${prompt.id}, null, 'rejected', true)"><i class="fas fa-ban"></i> ยกเลิกการอนุมัติ (และปฏิเสธ)</button>`;
            } else if (prompt.status_class === 'rejected') {
                 actionsContainer.innerHTML += `<button class="btn btn-success" onclick="approveSellablePrompt(${prompt.id}, null, true)"><i class="fas fa-redo"></i> อนุมัติใหม่</button> `;
            }
            actionsContainer.innerHTML += `<button class="btn" style="background-color:#6c757d;" onclick="closeModal('marketplace-prompt-detail-modal')"><i class="fas fa-times-circle"></i> ปิด</button>`;
            openModal('marketplace-prompt-detail-modal');
        }


        function renderMarketplacePromptsPagination(currentPage, totalPages) {
            const pagination = document.getElementById('marketplace-prompts-pagination');
            pagination.innerHTML = ''; if (totalPages <= 1) return;
            const prevBtn = document.createElement('button'); prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>'; prevBtn.disabled = currentPage === 1; prevBtn.onclick = () => loadPendingSellablePrompts(currentPage - 1); pagination.appendChild(prevBtn);
            const maxPgs = 5; let sPage = Math.max(1,currentPage - Math.floor(maxPgs/2)); let ePage = Math.min(totalPages,sPage + maxPgs -1); if(ePage-sPage+1<maxPgs){sPage=Math.max(1,ePage-maxPgs+1);}
            for (let i = sPage; i <= ePage; i++) { const pageBtn = document.createElement('button'); pageBtn.textContent = i; pageBtn.className = i === currentPage ? 'active' : ''; pageBtn.onclick = () => loadPendingSellablePrompts(i); pagination.appendChild(pageBtn); }
            const nextBtn = document.createElement('button'); nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>'; nextBtn.disabled = currentPage === totalPages; nextBtn.onclick = () => loadPendingSellablePrompts(currentPage + 1); pagination.appendChild(nextBtn);
        }
        function approveSellablePrompt(promptId, buttonElement, closeModalAfter = false) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะอนุมัติ Prompt นี้?')) return;
            const formData = new FormData(); formData.append('action', 'approve_sellable_prompt'); formData.append('prompt_id', promptId);
            if(buttonElement) buttonElement.disabled = true;
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) { showAlert('success', d.message); loadPendingSellablePrompts(currentMarketplacePromptsPage); if(closeModalAfter) closeModal('marketplace-prompt-detail-modal'); } else { showAlert('error', d.message || 'เกิดข้อผิดพลาดในการอนุมัติ'); } if(buttonElement) buttonElement.disabled = false; }).catch(e => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Approve)'); console.error('Error approving prompt:', e); if(buttonElement) buttonElement.disabled = false; });
        }
        function rejectSellablePrompt(promptId, buttonElement, newStatus = 'rejected', closeModalAfter = false) {
            const confirmMessage = newStatus === 'rejected' ? 'คุณแน่ใจหรือไม่ที่จะปฏิเสธ Prompt นี้?' : 'คุณแน่ใจหรือไม่ที่จะดำเนินการนี้?';
            if (!confirm(confirmMessage)) return;
            const formData = new FormData(); formData.append('action', 'reject_sellable_prompt'); formData.append('prompt_id', promptId); formData.append('new_status', newStatus);
            if(buttonElement) buttonElement.disabled = true;
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) { showAlert('success', d.message); loadPendingSellablePrompts(currentMarketplacePromptsPage); if(closeModalAfter) closeModal('marketplace-prompt-detail-modal'); } else { showAlert('error', d.message || 'เกิดข้อผิดพลาดในการปฏิเสธ'); } if(buttonElement) buttonElement.disabled = false; }).catch(e => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Reject)'); console.error('Error rejecting prompt:', e); if(buttonElement) buttonElement.disabled = false; });
        }
        function loadMarketplaceSettings() { /* Values are pre-filled by PHP */ }
         // --- END: Marketplace Admin JS ---

        function loadUsers(page = 1) {
            currentUsersPage = page; 
            const search = document.getElementById('user-search')?.value || '';
            const filter = document.getElementById('user-filter')?.value || 'all';
            const formData = new FormData(); formData.append('action', 'get_users'); formData.append('page', page); formData.append('search', search); formData.append('filter', filter);
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) { renderUsersTable(d.data.users); renderUserPagination(d.data.page, d.data.pages); } else { showAlert('error', d.message); } }).catch(e => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Users)'); console.error('Error:', e); });
        }
        function renderUsersTable(users) {
            const tbody = document.getElementById('users-tbody'); tbody.innerHTML = '';
            if (users.length === 0) { tbody.innerHTML = '<tr><td colspan="11" class="placeholder-message"><i class="fas fa-users"></i><p>ไม่พบข้อมูลสมาชิก</p></td></tr>'; return; }
            users.forEach(user => {
                const memberBadgeClass = user.member_type === 'free' ? 'free' : (user.member_type === 'monthly' ? 'monthly' : 'yearly');
                const memberLabel = user.member_type === 'free' ? 'ฟรี' : (user.member_type === 'monthly' ? 'รายเดือน' : 'รายปี');
                const avatar = user.avatar_url || avatarPlaceholder; // Use defined placeholder
                const expireDate = user.expire_date ? new Date(user.expire_date).toLocaleDateString('th-TH') : '-';
                const createdDate = new Date(user.created_at).toLocaleDateString('th-TH');
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td><img src="${htmlspecialchars(avatar)}" class="user-avatar" onerror="this.src='${avatarPlaceholder}'"></td>
                    <td><strong>${htmlspecialchars(user.username)}</strong></td>
                    <td>${htmlspecialchars(user.full_name)}</td>
                    <td>${htmlspecialchars(user.email)}</td>
                    <td><span class="member-badge ${memberBadgeClass}">${htmlspecialchars(memberLabel)}</span></td>
                    <td>${user.points_balance !== null ? user.points_balance : 0} <i class="fas fa-coins" style="color: #f59e0b;"></i></td>
                    <td>${expireDate}</td>
                    <td><span class="status-badge ${user.status}">${user.status}</span></td>
                    <td>${createdDate}</td>
                    <td class="item-actions">
                        <button class="btn btn-sm" onclick="editUser(${user.id})" style="background-color:#3b82f6;"><i class="fas fa-user-edit"></i></button>
                        ${user.id !== 1 ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})"><i class="fas fa-user-times"></i></button>` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        function renderUserPagination(currentPage, totalPages) {
            const pagination = document.getElementById('users-pagination'); pagination.innerHTML = ''; if (totalPages <= 1) return;
            const prevBtn = document.createElement('button'); prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>'; prevBtn.disabled = currentPage === 1; prevBtn.onclick = () => loadUsers(currentPage - 1); pagination.appendChild(prevBtn);
            const maxPgs = 5; let sPage = Math.max(1,currentPage - Math.floor(maxPgs/2)); let ePage = Math.min(totalPages,sPage + maxPgs -1); if(ePage-sPage+1<maxPgs){sPage=Math.max(1,ePage-maxPgs+1);}
            for (let i = sPage; i <= ePage; i++) { const pageBtn = document.createElement('button'); pageBtn.textContent = i; pageBtn.className = i === currentPage ? 'active' : ''; pageBtn.onclick = () => loadUsers(i); pagination.appendChild(pageBtn); }
            const nextBtn = document.createElement('button'); nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>'; nextBtn.disabled = currentPage === totalPages; nextBtn.onclick = () => loadUsers(currentPage + 1); pagination.appendChild(nextBtn);
        }
        function searchUsers() { loadUsers(1); }
        function editUser(userId) {
            currentEditingUser = userId;
            fetch('admin.php', { method: 'POST', body: new URLSearchParams({action: 'get_user', user_id: userId}) })
            .then(response => response.json()).then(data => {
                if (data.success) {
                    const user = data.data;
                    document.getElementById('user-info-display').innerHTML = `<strong>ID:</strong> ${user.id} | <strong>Username:</strong> ${htmlspecialchars(user.username)} <br><strong>Email:</strong> ${htmlspecialchars(user.email)}`;
                    document.getElementById('user-member-type').value = user.member_type;
                    document.getElementById('user-expire-date').value = user.expire_date || '';
                    document.getElementById('user-points-balance').value = user.points_balance === null ? 0 : user.points_balance;
                    handleMemberTypeChange(); openModal('user-modal');
                } else { showAlert('error', data.message); }
            }).catch(error => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Get User)'); console.error('Error:', error); });
        }
        function handleMemberTypeChange() {
            const memberType = document.getElementById('user-member-type').value;
            const expireDateGroup = document.getElementById('expire-date-group');
            const expireDateInput = document.getElementById('user-expire-date');
            if (memberType === 'free') { expireDateGroup.style.display = 'none'; expireDateInput.value = ''; } 
            else { expireDateGroup.style.display = 'block'; if (!expireDateInput.value && (memberType === 'monthly' || memberType === 'yearly')) { const today = new Date(); if (memberType === 'monthly') today.setMonth(today.getMonth() + 1); else if (memberType === 'yearly') today.setFullYear(today.getFullYear() + 1); expireDateInput.value = today.toISOString().split('T')[0]; }}
        }
        function saveUserMembership() {
            if (!currentEditingUser) return;
            const memberType = document.getElementById('user-member-type').value;
            let expireDate = document.getElementById('user-expire-date').value;
            const pointsBalance = document.getElementById('user-points-balance').value;
            if (memberType === 'free') expireDate = '';
            const formData = new FormData(); formData.append('action', 'update_user_membership'); formData.append('user_id', currentEditingUser); formData.append('member_type', memberType); formData.append('expire_date', expireDate); formData.append('points_balance', pointsBalance);
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if (d.success) { closeModal('user-modal'); loadUsers(currentUsersPage); showAlert('success', d.message); } else { showAlert('error', d.message); } }).catch(e => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Update User)'); console.error('Error:', e); });
        }
        function deleteUser(userId) {
            if (userId === 1) { showAlert('error', 'ไม่สามารถลบบัญชี Admin หลักได้'); return; }
            if (confirm(`คุณแน่ใจหรือไม่ที่จะลบผู้ใช้ ID: ${userId}? การกระทำนี้จะเปลี่ยนสถานะเป็น "deleted"`)) {
                fetch('admin.php', { method: 'POST', body: new URLSearchParams({action: 'delete_user', user_id: userId}) }).then(r => r.json()).then(d => { if (d.success) { loadUsers(currentUsersPage); showAlert('success', d.message); } else { showAlert('error', d.message); } }).catch(e => { showAlert('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ (Delete User)'); console.error('Error:', e); });
            }
        }
        function saveGeneralMessages() {
            const formData = new FormData(); formData.append('action', 'save_settings');
            formData.append('settings[placeholder_title]', document.getElementById('placeholder-title').value);
            formData.append('settings[placeholder_description]', document.getElementById('placeholder-description').value);
            formData.append('settings[gallery_title]', document.getElementById('gallery-title-input-setting').value);
            formData.append('settings[gallery_description]', document.getElementById('gallery-description-setting').value);
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => showAlert(d.success ? 'success' : 'error', d.message))
            .catch(e => { showAlert('error', 'Error (Save General Messages)'); console.error('Error:', e); });
        }
        function saveGeneralSettings() {
            const formData = new FormData(); formData.append('action', 'save_settings');
            formData.append('settings[online_count]', document.getElementById('online-count').value);
            formData.append('settings[site_title]', document.getElementById('site-title').value);
            formData.append('settings[site_description]', document.getElementById('site-description').value);
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => showAlert(d.success ? 'success' : 'error', d.message))
            .catch(e => { showAlert('error', 'Error (Save General Settings)'); console.error('Error:', e); });
        }
        function saveMarketplaceSettings() {
            const formData = new FormData(); formData.append('action', 'update_marketplace_settings');
            formData.append('settings[commission_rate]', document.getElementById('commission-rate').value);
            formData.append('settings[default_prompt_price]', document.getElementById('default-prompt-price').value);
            fetch('admin.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => showAlert(d.success ? 'success' : 'error', d.message))
            .catch(e => { showAlert('error', 'Error (Save MP Settings)'); console.error('Error:', e); });
        }
        function openModal(modalId) { const m = document.getElementById(modalId); if(m) m.classList.add('active'); else console.error('Modal not found: ' + modalId); }
        function closeModal(modalId) { const m = document.getElementById(modalId); if(m) m.classList.remove('active'); }
        function previewImage(url, previewId) { const p = document.getElementById(previewId); if(url){ p.src=url; p.style.display='block'; p.onerror=()=>{p.style.display='none';showAlert('error','โหลดรูปไม่ได้');};}else{p.style.display='none';}}
        function saveExample(){const t=document.getElementById('example-title').value.trim(),p=document.getElementById('example-prompt').value.trim(),i=document.getElementById('example-icon').value.trim()||'fas fa-lightbulb';if(!t||!p){showAlert('error','กรอกข้อมูล Example');return;}const fd=new FormData();fd.append('action','save_example');fd.append('title',t);fd.append('prompt',p);fd.append('icon',i);if(currentEditingExample)fd.append('id',currentEditingExample);fetch('admin.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){closeModal('example-modal');refreshData(true);showAlert('success',d.message);}else{showAlert('error',d.message);}}).catch(e=>showAlert('error','Error (Save Ex)'));}
        function deleteExample(id){if(confirm('ลบ Prompt ยอดนิยมนี้?')){fetch('admin.php',{method:'POST',body:new URLSearchParams({action:'delete_example',id:id})}).then(r=>r.json()).then(d=>{if(d.success){refreshData(true);showAlert('success',d.message);}else{showAlert('error',d.message);}}).catch(e=>showAlert('error','Error (Del Ex)'));}}
        function loadExamples(){const c=document.getElementById('examples-list');c.innerHTML='';if(examplesData.length===0){c.innerHTML='<div class="placeholder-message"><i class="fas fa-lightbulb"></i><p>ยังไม่มี Prompt ยอดนิยม</p></div>';return;}examplesData.forEach(ex=>{const ca=document.createElement('div');ca.className='item-card';ca.innerHTML=`<div class="item-header"><div class="item-title"><i class="${htmlspecialchars(ex.icon)}"></i> ${htmlspecialchars(ex.title)}</div><div class="item-actions"><button class="btn btn-sm" onclick="openExampleModal(${ex.id})"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="deleteExample(${ex.id})"><i class="fas fa-trash"></i></button></div></div><div class="item-content">${htmlspecialchars(ex.prompt)}</div>`;c.appendChild(ca);});}
        function openExampleModal(editId=null){currentEditingExample=editId;const m=document.getElementById('example-modal'),tE=document.getElementById('example-modal-title');if(editId){const ex=examplesData.find(e=>e.id==editId);tE.textContent='แก้ไข Prompt ยอดนิยม';document.getElementById('example-title').value=ex.title;document.getElementById('example-prompt').value=ex.prompt;document.getElementById('example-icon').value=ex.icon;}else{tE.textContent='เพิ่ม Prompt ยอดนิยม';document.getElementById('example-title').value='';document.getElementById('example-prompt').value='';document.getElementById('example-icon').value='fas fa-lightbulb';}m.classList.add('active');}
        function saveGalleryItem(){const t=document.getElementById('gallery-title-input').value.trim(),d=document.getElementById('gallery-desc').value.trim(),iu=document.getElementById('gallery-image').value.trim(),p=document.getElementById('gallery-prompt').value.trim(),i=document.getElementById('gallery-icon').value.trim()||'fas fa-image';if(!t||!iu||!p){showAlert('error','กรอกข้อมูล Gallery');return;}const fd=new FormData();fd.append('action','save_gallery');fd.append('title',t);fd.append('description',d);fd.append('image_url',iu);fd.append('prompt',p);fd.append('icon',i);if(currentEditingGallery)fd.append('id',currentEditingGallery);fetch('admin.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){closeModal('gallery-modal');refreshData(true);showAlert('success',d.message);}else{showAlert('error',d.message);}}).catch(e=>showAlert('error','Error (Save Gal)'));}
        function deleteGalleryItem(id){if(confirm('ลบรายการแกลเลอรี่นี้?')){fetch('admin.php',{method:'POST',body:new URLSearchParams({action:'delete_gallery',id:id})}).then(r=>r.json()).then(d=>{if(d.success){refreshData(true);showAlert('success',d.message);}else{showAlert('error',d.message);}}).catch(e=>showAlert('error','Error (Del Gal)'));}}
        function loadGallery(){const c=document.getElementById('gallery-list');c.innerHTML='';if(galleryData.length===0){c.innerHTML='<div class="placeholder-message"><i class="fas fa-photo-video"></i><p>ยังไม่มีรายการแกลเลอรี่</p></div>';return;}galleryData.forEach(it=>{const ca=document.createElement('div');ca.className='item-card gallery-item-card';ca.innerHTML=`<img src="${htmlspecialchars(it.image_url)}" alt="${htmlspecialchars(it.title)}" class="gallery-image-preview" loading="lazy" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<div style=\\"width:150px; height:100px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:8px; color:#999; font-size:0.8em;\\">No Image</div>')"><div><div class="item-header"><div class="item-title"><i class="${htmlspecialchars(it.icon)}"></i> ${htmlspecialchars(it.title)}</div><div class="item-actions"><button class="btn btn-sm" onclick="openGalleryModal(${it.id})"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="deleteGalleryItem(${it.id})"><i class="fas fa-trash"></i></button></div></div><div style="color:#6b7280;font-size:0.85em;margin-bottom:10px;">${htmlspecialchars(it.description)}</div><div class="item-content">${htmlspecialchars(it.prompt)}</div></div>`;c.appendChild(ca);});}
        function openGalleryModal(editId=null){currentEditingGallery=editId;const m=document.getElementById('gallery-modal'),tE=document.getElementById('gallery-modal-title'),pr=document.getElementById('gallery-preview');if(editId){const it=galleryData.find(g=>g.id==editId);tE.textContent='แก้ไขรายการแกลเลอรี่';document.getElementById('gallery-title-input').value=it.title;document.getElementById('gallery-desc').value=it.description;document.getElementById('gallery-image').value=it.image_url;document.getElementById('gallery-prompt').value=it.prompt;document.getElementById('gallery-icon').value=it.icon;previewImage(it.image_url,'gallery-preview');}else{tE.textContent='เพิ่มรายการแกลเลอรี่';document.getElementById('gallery-title-input').value='';document.getElementById('gallery-desc').value='';document.getElementById('gallery-image').value='';document.getElementById('gallery-prompt').value='';document.getElementById('gallery-icon').value='fas fa-image';previewImage('','gallery-preview');}m.classList.add('active');}
        function refreshData(showSuccess=false){fetch('admin.php',{method:'POST',body:new URLSearchParams({action:'get_data'})}).then(r=>r.json()).then(d=>{if(d.success){examplesData=d.data.examples;galleryData=d.data.gallery;document.getElementById('total-examples').textContent=d.data.stats.total_examples;document.getElementById('total-gallery').textContent=d.data.stats.total_gallery;document.getElementById('total-user-prompts').textContent=d.data.stats.total_user_prompts;document.getElementById('today-prompts').textContent=d.data.stats.today_prompts;document.getElementById('stat-sellable-prompts').textContent=d.data.stats.total_sellable_prompts||0;document.getElementById('stat-transactions').textContent=d.data.stats.total_transactions||0;const activeSec=document.querySelector('.admin-section.active');if(activeSec)showSection(activeSec.id);if(showSuccess)showAlert('success','ข้อมูลอัปเดตแล้ว');}else{if(showSuccess)showAlert('error','อัปเดตข้อมูลไม่ได้');}}).catch(e=>{if(showSuccess)showAlert('error','Error (Refresh)');console.error('Error refreshing data:',e);});}
        function updateStats(){refreshData(false);}
        function showAlert(type,message){const ac=document.getElementById('alert-container');const ad=document.createElement('div');ad.className=`alert ${type}`;ad.innerHTML=`<i class="fas fa-${type==='success'?'check-circle':'exclamation-triangle'}"></i> ${htmlspecialchars(message)}`;ad.style.display='block';ac.appendChild(ad);setTimeout(()=>{ad.style.opacity=0;setTimeout(()=>ad.remove(),300);},4000);}
        function logout(){if(confirm('ออกจากระบบ?')){fetch('admin.php',{method:'POST',body:new URLSearchParams({action:'logout'})}).then(r=>r.json()).then(d=>{if(d.success)window.location.href='admin.php';}).catch(e=>window.location.href='admin.php');}}
        
        window.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nav-btn').forEach(btn => {
                const section = btn.dataset.section;
                // btn.removeAttribute('onclick'); // Removed if you prefer JS-only event handling
                btn.addEventListener('click', (event) => showSection(section, event));
            });
            showSection('dashboard', null); 
            const searchInput = document.getElementById('user-search'); if (searchInput) searchInput.addEventListener('keypress', e => { if (e.key === 'Enter') searchUsers(); });
            const memberTypeSelect = document.getElementById('user-member-type'); if (memberTypeSelect) memberTypeSelect.addEventListener('change', handleMemberTypeChange);
            const marketplaceFilter = document.getElementById('marketplace-prompt-filter');
            if(marketplaceFilter) marketplaceFilter.value = 'pending_approval';
        });
        window.addEventListener('click', function(event) { if (event.target.classList.contains('modal')) event.target.classList.remove('active'); });
        document.addEventListener('keydown', function(event) { if (event.key === 'Escape') document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active')); });
        setInterval(updateStats, 60000);
    </script>
</body>
</html>