<?php
// เปิด debug mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เพิ่ม error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

require_once 'config.php';

// Log debug info
error_log("=== Save User Prompt Debug ===");
error_log("Session ID: " . session_id());
error_log("Session User ID: " . ($_SESSION['user_id'] ?? 'none'));
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

header('Content-Type: application/json; charset=utf-8');

try {
    // รับข้อมูลจาก JavaScript
    $json = file_get_contents('php://input');
    error_log("Raw JSON input: " . $json);
    
    $data = json_decode($json, true);
    error_log("Parsed data: " . print_r($data, true));

    if (!$data) {
        error_log("JSON decode failed: " . json_last_error_msg());
        jsonResponse(false, 'ข้อมูลไม่ถูกต้อง: ' . json_last_error_msg());
    }

    $db = Database::getInstance();
    $promptManager = new PromptManager();
    
    // ตรวจสอบการใช้งานและขีดจำกัด
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    error_log("User ID: " . ($user_id ?? 'null'));
    error_log("IP Address: " . $ip_address);
    
    if ($user_id) {
        // ผู้ใช้ที่ล็อกอิน - ตรวจสอบข้อมูลสมาชิก
        $user = $db->select("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = $user[0] ?? null;
        
        if (!$user) {
            error_log("User not found for ID: " . $user_id);
            jsonResponse(false, 'ไม่พบข้อมูลผู้ใช้');
        }
        
        $member_type = $user['member_type'] ?? 'free';
        error_log("Member type: " . $member_type);
        
        // กำหนดขีดจำกัดและเงื่อนไขตามประเภทสมาชิก
        if ($member_type == 'monthly') {
            $limit = 60; // 60 ครั้งต่อเดือน
            $where = "user_id = $user_id AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
            $period = 'เดือนนี้';
        } elseif ($member_type == 'yearly') {
            $limit = 999999; // ไม่จำกัด (ตั้งเป็นจำนวนมาก)
            $where = "user_id = $user_id AND YEAR(created_at) = YEAR(NOW())";
            $period = 'ปีนี้';
        } else {
            // สมาชิกฟรีที่ล็อกอิน - 10 ครั้งต่อวัน
            $limit = 10;
            $where = "user_id = $user_id AND DATE(created_at) = CURDATE()";
            $period = 'วันนี้';
        }
    } else {
        // ผู้ใช้ที่ไม่ได้ล็อกอิน - 5 ครั้งต่อวัน ต่อ IP
        $limit = 5;
        $where = "(user_id IS NULL OR user_id = 0) AND ip_address = '$ip_address' AND DATE(created_at) = CURDATE()";
        $period = 'วันนี้';
    }
    
    error_log("Limit: " . $limit);
    error_log("Where clause: " . $where);
    
    // นับจำนวน prompt ที่สร้างแล้ว
    $count_sql = "SELECT COUNT(*) as count FROM user_prompts WHERE $where";
    error_log("Count SQL: " . $count_sql);
    
    $count_result = $db->select($count_sql);
    $current_count = $count_result[0]['count'] ?? 0;
    
    error_log("Current count: " . $current_count);
    
    // ตรวจสอบว่าเกินขีดจำกัดหรือไม่
    if ($limit != 999999 && $current_count >= $limit) {
        $status_text = $user_id ? 
            ($member_type == 'free' ? 'สมาชิกฟรี' : 'สมาชิกเช่าซื้อ') : 
            'ผู้ใช้ทั่วไป';
            
        error_log("Limit exceeded. Status: " . $status_text);
        jsonResponse(false, "คุณใช้สิทธิ์ครบแล้วสำหรับ{$period} ({$status_text}: {$limit} ครั้ง)" . 
                     ($user_id && $member_type == 'free' ? ' - สมัครแพ็กเกจเช่าซื้อเพื่อเพิ่มสิทธิ์' : ''));
    }
    
    // บันทึก prompt ใหม่
    $saveData = [
        'user_id' => $user_id,
        'subject' => $data['subject'] ?? '',
        'content_type' => $data['content_type'] ?? '',
        'style' => $data['style'] ?? '',
        'scene' => $data['scene'] ?? '',
        'details' => $data['details'] ?? '',
        'generated_prompt' => $data['generated_prompt'] ?? '',
        'ip_address' => $ip_address,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    error_log("Save data: " . print_r($saveData, true));
    
    $result = $promptManager->saveUserPrompt($saveData);
    error_log("Save result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        $remaining = $limit == 999999 ? 'ไม่จำกัด' : ($limit - $current_count - 1);
        $message = "บันทึก Prompt สำเร็จ - เหลือสิทธิ์ {$remaining} ครั้ง สำหรับ{$period}";
        error_log("Success message: " . $message);
        jsonResponse(true, $message);
    } else {
        error_log("Failed to save prompt");
        jsonResponse(false, 'เกิดข้อผิดพลาดในการบันทึก');
    }
    
} catch (Exception $e) {
    error_log('Error saving user prompt: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    jsonResponse(false, 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage());
}
?>