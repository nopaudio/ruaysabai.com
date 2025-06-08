<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // รับข้อมูลจาก POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        jsonResponse(false, 'ข้อมูลไม่ถูกต้อง');
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $required_fields = ['subject', 'generated_prompt'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            jsonResponse(false, "ข้อมูล {$field} จำเป็นต้องกรอก");
        }
    }
    
    $promptManager = new PromptManager();
    
    // *** แก้ไขหลัก: เรียกใช้ฟังก์ชัน checkAndLogUsage ที่ใช้งานได้แล้ว ***
    $promptData = [
        'subject' => cleanInput($input['subject'] ?? ''),
        'content_type' => cleanInput($input['content_type'] ?? 'general'),
        'style' => cleanInput($input['style'] ?? 'default'),
        'scene' => cleanInput($input['scene'] ?? ''),
        'details' => json_encode($input['details'] ?? [], JSON_UNESCAPED_UNICODE),
        'generated_prompt' => cleanInput($input['generated_prompt'] ?? ''),
    ];
    
    // ตรวจสอบและบันทึกการใช้งาน
    $result = $promptManager->checkAndLogUsage($promptData);
    
    // ส่งผลลัพธ์กลับ
    jsonResponse($result['success'], $result['message'], $result['data'] ?? null);

} catch (Exception $e) {
    // Log error
    Logger::error('Error in save_user_prompt: ' . $e->getMessage(), [
        'input' => $input ?? null,
        'trace' => $e->getTraceAsString()
    ]);
    
    jsonResponse(false, 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง');
}
?>