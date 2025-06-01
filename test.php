<?php
require_once 'config.php';

echo "<h1>ตรวจสอบการตั้งค่า</h1>";

$promptManager = new PromptManager();

$settings = [
    'site_title' => $promptManager->getSetting('site_title'),
    'site_description' => $promptManager->getSetting('site_description'),
    'online_count' => $promptManager->getSetting('online_count'),
    'placeholder_title' => $promptManager->getSetting('placeholder_title'),
    'placeholder_description' => $promptManager->getSetting('placeholder_description'),
    'gallery_title' => $promptManager->getSetting('gallery_title'),
    'gallery_description' => $promptManager->getSetting('gallery_description')
];

echo "<h2>การตั้งค่าปัจจุบัน:</h2>";
foreach ($settings as $key => $value) {
    echo "<p><strong>$key:</strong> " . htmlspecialchars($value) . "</p>";
}

// ตรวจสอบตาราง site_settings
echo "<h2>ข้อมูลในตาราง site_settings:</h2>";
$result = $promptManager->db->select("SELECT * FROM site_settings");
foreach ($result as $row) {
    echo "<p><strong>" . $row['setting_key'] . ":</strong> " . htmlspecialchars($row['setting_value']) . "</p>";
}
?>