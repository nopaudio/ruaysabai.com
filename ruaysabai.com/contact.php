<?php
// นำเข้าไฟล์โครงสร้างพื้นฐาน
require_once 'structure.php';

// ตรวจสอบการส่งค่าจากฟอร์มติดต่อ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact-submit'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // ในกรณีจริงควรส่งอีเมลหรือบันทึกลงฐานข้อมูล
    $success = true;
    
    if ($success) {
        // ตั้งค่าข้อความแจ้งเตือน
        $contactMessage = "ส่งข้อความติดต่อสำเร็จ! เราจะตอบกลับภายใน 24 ชั่วโมง";
        $_SESSION['contact_message'] = $contactMessage;
        
        // Redirect เพื่อป้องกันการส่งฟอร์มซ้ำ
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "เกิดข้อผิดพลาดในการส่งข้อความ กรุณาลองใหม่อีกครั้ง";
    }
}

// ดึงข้อความแจ้งเตือนจาก session (ถ้ามี)
$contactMessage = '';
if (isset($_SESSION['contact_message'])) {
    $contactMessage = $_SESSION['contact_message'];
    unset($_SESSION['contact_message']); // ลบข้อความหลังจากแสดงแล้ว
}

// แสดงส่วน header
renderHeader('ติดต่อเรา - ระบบหวยออนไลน์', $isLoggedIn, $userBalance);
?>

<section class="page-title">
    <div class="container">
        <h1>ติดต่อเรา</h1>
        <p>หากมีคำถามหรือข้อสงสัย สามารถติดต่อเราได้ที่นี่</p>
    </div>
</section>

<div class="container">
    <?php if (!empty($contactMessage)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $contactMessage; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="contact-wrapper">
        <section class="section contact-form-section">
            <h2 class="section-title">ส่งข้อความถึงเรา</h2>
            
            <form id="contact-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="name">ชื่อ</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="ชื่อของคุณ" required>
                </div>
                
                <div class="form-group">
                    <label for="email">อีเมล</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="อีเมลของคุณ" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">หัวข้อ</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="หัวข้อของคุณ" required>
                </div>
                
                <div class="form-group">
                    <label for="message">ข้อความ</label>
                    <textarea id="message" name="message" class="form-control" rows="5" placeholder="พิมพ์ข้อความของคุณที่นี่" required></textarea>
                </div>
                
                <button type="submit" name="contact-submit" class="btn btn-accent">ส่งข้อความ</button>
            </form>
        </section>
        
        <section class="section contact-info-section">
            <h2 class="section-title">ข้อมูลติดต่อ</h2>
            
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>ที่อยู่</h3>
                        <p>123/456 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>โทรศัพท์</h3>
                        <p>02-123-4567</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>อีเมล</h3>
                        <p>support@lotterythai.com</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fab fa-line"></i>
                    <div>
                        <h3>Line ID</h3>
                        <p>@lotterythai</p>
                    </div>
                </div>
            </div>
            
            <div class="office-hours">
                <h3>เวลาทำการ</h3>
                <p>จันทร์ - ศุกร์: 9:00 - 18:00 น.</p>
                <p>เสาร์: 9:00 - 14:00 น.</p>
                <p>อาทิตย์: ปิดทำการ</p>
            </div>
        </section>
    </div>
    
    <section class="section">
        <h2 class="section-title">แผนที่</h2>
        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.5598836510673!2d100.55994367584543!3d13.722930286645546!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29f38f0cd961f%3A0xeba2412d94442978!2z4Liq4Li44LiC4Li44Lih4Lin4Li04LiV!5e0!3m2!1sen!2sth!4v1651123456789!5m2!1sen!2sth" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>
</div>

<style>
.contact-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.contact-info {
    margin-bottom: 2rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.contact-item i {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-right: 1rem;
    width: 24px;
    text-align: center;
}

.contact-item h3 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    color: var(--primary-color);
}

.office-hours {
    background-color: #f9f9f9;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #eee;
}

.office-hours h3 {
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.map-container {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

@media (max-width: 768px) {
    .contact-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// แสดงส่วน footer
renderFooter();
?>