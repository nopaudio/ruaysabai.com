<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // รับข้อมูลจาก JavaScript
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonResponse(false, 'ข้อมูลไม่ถูกต้อง');
    }

    $db = Database::getInstance();
    
    // ตรวจสอบการใช้งานและขีดจำกัด
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if ($user_id) {
        // ผู้ใช้ที่ล็อกอิน - นับตาม user_id
        $user = $db->select("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = $user[0] ?? null;
        
        if (!$user) {
            jsonResponse(false, 'ไม่พบข้อมูลผู้ใช้');
        }
        
        $member_type = $user['member_type'] ?? 'free';
        
        // กำหนดขีดจำกัดตามประเภทสมาชิก
        if ($member_type == 'monthly') {
            $limit = 60;
            $count_sql = "SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
            $count_params = [$user_id];
            $period = 'เดือนนี้';
        } elseif ($member_type == 'yearly') {
            $limit = 999999; // ไม่จำกัด
            $count_sql = "SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ?";
            $count_params = [$user_id];
            $period = 'ปีนี้';
        } else {
            // สมาชิกฟรี
            $limit = 10;
            $count_sql = "SELECT COUNT(*) as count FROM user_prompts WHERE user_id = ? AND DATE(created_at) = CURDATE()";
            $count_params = [$user_id];
            $period = 'วันนี้';
        }
        
        $count_result = $db->select($count_sql, $count_params);
        $current_count = $count_result[0]['count'] ?? 0;
        
    } else {
        // ผู้ใช้ทั่วไป (ไม่ได้ล็อกอิน) - นับตาม IP address
        $limit = 5;
        $period = 'วันนี้';
        
        // นับตาม IP address แต่ละ IP แยกกัน
        $count_sql = "SELECT COUNT(*) as count FROM user_prompts WHERE ip_address = ? AND DATE(created_at) = CURDATE()";
        $count_params = [$ip_address];
        
        $count_result = $db->select($count_sql, $count_params);
        $current_count = $count_result[0]['count'] ?? 0;
        
        $member_type = 'guest';
    }
    
    // ตรวจสอบขีดจำกัด
    if ($limit != 999999 && $current_count >= $limit) {
        $status_text = $user_id ? 
            ($member_type == 'free' ? 'สมาชิกฟรี' : 'สมาชิกพรีเมียม') : 
            'ผู้ใช้ทั่วไป';
            
        jsonResponse(false, "คุณใช้สิทธิ์ครบแล้วสำหรับ{$period} ({$status_text}: {$limit} ครั้ง)" . 
                     ($user_id && $member_type == 'free' ? ' - อัปเกรดสมาชิกเพื่อเพิ่มสิทธิ์' : ''));
    }
    
    // เตรียมข้อมูลสำหรับบันทึก
    $saveData = [
        'user_id' => $user_id,  // NULL สำหรับ guest, จำนวน สำหรับ user
        'ip_address' => $ip_address,  // บันทึก IP ทุกครั้ง
        'subject' => $data['subject'] ?? '',
        'content_type' => $data['content_type'] ?? '',
        'style' => $data['style'] ?? '',
        'scene' => $data['scene'] ?? '',
        'details' => $data['details'] ?? '',
        'generated_prompt' => $data['generated_prompt'] ?? ''
    ];
    
    // บันทึกข้อมูล
    $result = $db->insert('user_prompts', $saveData);
    
    if ($result) {
        $remaining = $limit == 999999 ? 'ไม่จำกัด' : ($limit - $current_count - 1);
        $message = "บันทึก Prompt สำเร็จ - เหลือสิทธิ์ {$remaining} ครั้ง สำหรับ{$period}";
        jsonResponse(true, $message);
    } else {
        $db_error = $db->getConnection()->error;
        error_log("Database error in save_user_prompt: " . $db_error);
        jsonResponse(false, 'เกิดข้อผิดพลาดในการบันทึก');
    }
    
} catch (Exception $e) {
    error_log('Error saving user prompt: ' . $e->getMessage());
    jsonResponse(false, 'เกิดข้อผิดพลาดในระบบ');
}
?>