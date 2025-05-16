<?php
/**
 * ไฟล์สำหรับการตรวจสอบสิทธิ์ผู้ดูแลระบบในส่วนหลังบ้าน
 * เพื่อป้องกันการเข้าถึงหน้าหลังบ้านโดยไม่ได้รับอนุญาต
 */

// เริ่ม session (หากยังไม่มีการเริ่ม)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามีการล็อกอินเป็นผู้ดูแลระบบหรือไม่
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
    // ถ้าไม่มีการล็อกอิน ให้บันทึก URL ที่พยายามเข้าถึง
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // แสดงข้อความแจ้งเตือน
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบในฐานะผู้ดูแลระบบก่อนเข้าใช้งาน";
    
    // ส่งไปยังหน้าล็อกอินของผู้ดูแลระบบ
    header('Location: login.php');
    exit();
}

// ตรวจสอบระดับสิทธิ์ของผู้ดูแลระบบ (ถ้าจำเป็น)
// บางหน้าอาจต้องการสิทธิ์ระดับผู้ดูแลระบบสูงสุดเท่านั้น
function checkAdminLevel($required_level) {
    // ถ้าไม่มีการล็อกอิน หรือ ระดับสิทธิ์ไม่เพียงพอ
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] < $required_level) {
        $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
        header('Location: index.php');
        exit();
    }
    return true;
}

// ฟังก์ชันตรวจสอบว่าเป็นผู้ดูแลระบบสูงสุดหรือไม่
function isSuper() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'super';
}

// ฟังก์ชันตรวจสอบว่าเป็นผู้ดูแลระบบทั่วไปหรือไม่
function isAdmin() {
    return isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] == 'admin' || $_SESSION['admin_role'] == 'super');
}

// ฟังก์ชันตรวจสอบว่าเป็นผู้จัดการหรือไม่
function isManager() {
    return isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] == 'manager' || $_SESSION['admin_role'] == 'admin' || $_SESSION['admin_role'] == 'super');
}

// ฟังก์ชันตรวจสอบว่าเป็นพนักงานหรือไม่
function isStaff() {
    return isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] == 'staff' || $_SESSION['admin_role'] == 'manager' || $_SESSION['admin_role'] == 'admin' || $_SESSION['admin_role'] == 'super');
}

// ฟังก์ชันดึงชื่อผู้ดูแลระบบ
function getAdminName() {
    global $conn;
    
    if (!isset($_SESSION['admin_id'])) {
        return 'ไม่ระบุชื่อ';
    }
    
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT username, first_name, last_name FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (!empty($admin['first_name']) && !empty($admin['last_name'])) {
            return $admin['first_name'] . ' ' . $admin['last_name'];
        } else {
            return $admin['username'];
        }
    }
    
    return 'ไม่ระบุชื่อ';
}

// ฟังก์ชันบันทึกประวัติการใช้งานระบบ
function logAdminActivity($action, $detail = '', $module = '') {
    global $conn;
    
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    $admin_id = $_SESSION['admin_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (empty($module)) {
        // ดึงค่า module จาก URL ปัจจุบัน
        $current_file = basename($_SERVER['PHP_SELF']);
        $module = str_replace('.php', '', $current_file);
    }
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, detail, module, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $admin_id, $action, $detail, $module, $ip_address, $user_agent);
    return $stmt->execute();
}
