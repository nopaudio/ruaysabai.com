<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // ตรวจสอบการล็อกอิน
    if (!isUserLoggedIn()) {
        jsonResponse(false, 'ไม่ได้ล็อกอิน');
    }

    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(false, 'ไม่พบข้อมูลผู้ใช้');
    }

    $db = Database::getInstance();
    $user_id = $user['id'];
    $member_type = $user['member_type'];

    // คำนวณสิทธิ์ที่เหลือ
    if ($member_type == 'monthly') {
        $limit = 60;
        $used = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);
        $remaining = $limit - ($used[0]['count'] ?? 0);
        $period = 'เดือนนี้';
    } elseif ($member_type == 'yearly') {
        $remaining = 'ไม่จำกัด';
        $limit = 'ไม่จำกัด';
        $period = 'ปีนี้';
    } else {
        $limit = 10;
        $used = $db->select("SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND DATE(created_at) = CURDATE()", [$user_id]);
        $remaining = $limit - ($used[0]['count'] ?? 0);
        $period = 'วันนี้';
    }

    jsonResponse(true, 'สำเร็จ', [
        'remaining' => $remaining,
        'limit' => $limit,
        'period' => $period,
        'member_type' => $member_type
    ]);

} catch (Exception $e) {
    error_log('Get usage stats error: ' . $e->getMessage());
    jsonResponse(false, 'เกิดข้อผิดพลาดในระบบ');
}
?>