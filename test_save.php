<?php
require_once 'config.php';

echo "<h1>ทดสอบการบันทึกการตั้งค่า</h1>";

$promptManager = new PromptManager();

// ทดสอบการบันทึก
echo "<h2>ก่อนบันทึก:</h2>";
echo "<p>site_title: " . $promptManager->getSetting('site_title') . "</p>";

// บันทึกใหม่
$result = $promptManager->setSetting('site_title', 'AI Prompt Generator Pro 777');

echo "<h2>ผลการบันทึก:</h2>";
echo "<p>Result: " . ($result ? 'สำเร็จ' : 'ล้มเหลว') . "</p>";

echo "<h2>หลังบันทึก:</h2>";
echo "<p>site_title: " . $promptManager->getSetting('site_title') . "</p>";

// ดูข้อมูลในตาราง
$settings = $promptManager->db->select("SELECT * FROM site_settings WHERE setting_key = 'site_title'");
echo "<h2>ข้อมูลในฐานข้อมูล:</h2>";
print_r($settings);
?>