<?php
// กำหนดพาธสำหรับ include ไฟล์ config หลัก
$configPath = dirname(__DIR__) . '/config.php';
require_once $configPath;

// ฟังก์ชันสำหรับตรวจสอบการล็อกอินของผู้ดูแลระบบ
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// ฟังก์ชันสำหรับบังคับให้ล็อกอินก่อนเข้าถึงหน้าผู้ดูแลระบบ
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนใช้งาน";
        header("Location: login.php");
        exit;
    }
}

// ฟังก์ชันสำหรับตรวจสอบสิทธิ์ผู้ดูแลระบบ
function checkAdminPermission($requiredRole = 'admin') {
    requireAdminLogin();
    
    $currentRole = $_SESSION['admin_role'];
    
    // ตรวจสอบสิทธิ์ตามลำดับความสำคัญ
    switch ($requiredRole) {
        case 'super_admin':
            if ($currentRole != 'super_admin') {
                $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้";
                header("Location: dashboard.php");
                exit;
            }
            break;
        case 'admin':
            if ($currentRole != 'super_admin' && $currentRole != 'admin') {
                $_SESSION['error'] = "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้";
                header("Location: dashboard.php");
                exit;
            }
            break;
        case 'moderator':
            // ทุกตำแหน่งสามารถเข้าถึงได้
            break;
        default:
            // ป้องกันกรณีที่ไม่ได้ระบุสิทธิ์ที่ต้องการ
            break;
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ดูแลระบบ
function getAdminData($admin_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = :admin_id");
    $stmt->execute(['admin_id' => $admin_id]);
    return $stmt->fetch();
}

// ฟังก์ชันสำหรับการลบไฟล์
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// ฟังก์ชันสำหรับแปลงสถานะเป็นภาษาไทย
function translateStatus($status, $type = 'general') {
    switch ($type) {
        case 'payment':
            switch ($status) {
                case 'pending':
                    return 'รอตรวจสอบ';
                case 'approved':
                    return 'อนุมัติแล้ว';
                case 'rejected':
                    return 'ถูกปฏิเสธ';
                default:
                    return $status;
            }
            break;
        case 'order':
            switch ($status) {
                case 'pending':
                    return 'รอดำเนินการ';
                case 'processing':
                    return 'กำลังดำเนินการ';
                case 'shipped':
                    return 'จัดส่งแล้ว';
                case 'completed':
                    return 'เสร็จสมบูรณ์';
                case 'cancelled':
                    return 'ยกเลิก';
                default:
                    return $status;
            }
            break;
        case 'user':
            switch ($status) {
                case 'active':
                    return 'ใช้งาน';
                case 'suspended':
                    return 'ระงับการใช้งาน';
                case 'pending':
                    return 'รอยืนยัน';
                default:
                    return $status;
            }
            break;
        default:
            switch ($status) {
                case 'active':
                    return 'เปิดใช้งาน';
                case 'inactive':
                    return 'ปิดใช้งาน';
                case 'out_of_stock':
                    return 'สินค้าหมด';
                default:
                    return $status;
            }
            break;
    }
}

// ฟังก์ชันสำหรับแสดงสถานะ HTML Badge
function getStatusBadge($status, $type = 'general') {
    $badgeClass = '';
    
    switch ($type) {
        case 'payment':
            switch ($status) {
                case 'pending':
                    $badgeClass = 'bg-warning text-dark';
                    break;
                case 'approved':
                    $badgeClass = 'bg-success';
                    break;
                case 'rejected':
                    $badgeClass = 'bg-danger';
                    break;
                default:
                    $badgeClass = 'bg-secondary';
                    break;
            }
            break;
        case 'order':
            switch ($status) {
                case 'pending':
                    $badgeClass = 'bg-warning text-dark';
                    break;
                case 'processing':
                    $badgeClass = 'bg-info';
                    break;
                case 'shipped':
                    $badgeClass = 'bg-primary';
                    break;
                case 'completed':
                    $badgeClass = 'bg-success';
                    break;
                case 'cancelled':
                    $badgeClass = 'bg-danger';
                    break;
                default:
                    $badgeClass = 'bg-secondary';
                    break;
            }
            break;
        case 'user':
            switch ($status) {
                case 'active':
                    $badgeClass = 'bg-success';
                    break;
                case 'suspended':
                    $badgeClass = 'bg-danger';
                    break;
                case 'pending':
                    $badgeClass = 'bg-warning text-dark';
                    break;
                default:
                    $badgeClass = 'bg-secondary';
                    break;
            }
            break;
        default:
            switch ($status) {
                case 'active':
                    $badgeClass = 'bg-success';
                    break;
                case 'inactive':
                    $badgeClass = 'bg-danger';
                    break;
                case 'out_of_stock':
                    $badgeClass = 'bg-warning text-dark';
                    break;
                default:
                    $badgeClass = 'bg-secondary';
                    break;
            }
            break;
    }
    
    return '<span class="badge ' . $badgeClass . '">' . translateStatus($status, $type) . '</span>';
}
?>