<?php
// ดึงยอดเงินของผู้ใช้
function getUserBalance($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return floatval($row['balance']);
    }
    
    return 0.00;
}

// ดึงข้อมูลผู้ใช้
function getUserData($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return null;
}

// บันทึกธุรกรรม
function addTransaction($user_id, $type, $amount, $description = '', $reference_id = null, $reference_type = null) {
    global $conn;
    
    // ดึงยอดเงินปัจจุบัน
    $balance_before = getUserBalance($user_id);
    
    // คำนวณยอดเงินหลังทำรายการ
    $balance_after = $balance_before;
    
    switch ($type) {
        case 'deposit':
        case 'win':
        case 'admin':
            $balance_after += $amount;
            break;
        case 'purchase':
            $balance_after -= $amount;
            // แปลงจำนวนเงินเป็นค่าลบสำหรับการซื้อ
            $amount = -abs($amount);
            break;
    }
    
    // บันทึกธุรกรรม
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isddddss", $user_id, $type, $amount, $balance_before, $balance_after, $reference_id, $reference_type, $description);
    
    if ($stmt->execute()) {
        // อัปเดตยอดเงินในตาราง users
        updateUserBalance($user_id, $balance_after);
        return $stmt->insert_id;
    }
    
    return false;
}

// อัปเดตยอดเงินของผู้ใช้
function updateUserBalance($user_id, $new_balance) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $new_balance, $user_id);
    
    return $stmt->execute();
}

// ตรวจสอบว่าเลขนี้ซื้อไปแล้วหรือยัง (ป้องกันการซื้อซ้ำ)
function isNumberAlreadyPurchased($user_id, $lottery_id, $number, $lottery_type) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchases 
                           WHERE user_id = ? AND lottery_id = ? AND number = ? AND lottery_type = ? AND status = 'pending'");
    $stmt->bind_param("iiss", $user_id, $lottery_id, $number, $lottery_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}

// แปลงชื่อประเภทหวยเป็นภาษาไทย
function getLotteryTypeText($type) {
    $types = [
        'firstPrize' => 'รางวัลที่ 1 (6 ตัว)',
        'frontThree' => '3 ตัวหน้า',
        'backThree' => '3 ตัวท้าย',
        'backTwo' => '2 ตัวท้าย'
    ];
    
    return $types[$type] ?? $type;
}

// ฟอร์แมตวันที่เป็นรูปแบบไทย
function formatThaiDate($date) {
    $thai_months = [
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    ];
    
    $date_arr = explode('-', date('d-m-Y', strtotime($date)));
    $thai_year = (int)$date_arr[2] + 543;
    
    return $date_arr[0].' '.$thai_months[$date_arr[1]].' '.$thai_year;
}

// แปลงสถานะเป็นภาษาไทย
function getStatusText($status, $type = 'lottery') {
    if ($type === 'lottery') {
        $statuses = [
            'pending' => 'รอผล',
            'win' => 'ถูกรางวัล',
            'lose' => 'ไม่ถูกรางวัล'
        ];
    } elseif ($type === 'deposit') {
        $statuses = [
            'pending' => 'รอตรวจสอบ',
            'success' => 'สำเร็จ',
            'failed' => 'ไม่สำเร็จ'
        ];
    } else {
        $statuses = [
            'active' => 'ใช้งาน',
            'blocked' => 'ระงับ',
            'pending' => 'รอดำเนินการ',
            'completed' => 'เสร็จสิ้น'
        ];
    }
    
    return $statuses[$status] ?? $status;
}

// สร้าง HTML สำหรับแสดงสถานะ
function getStatusBadge($status, $type = 'lottery') {
    $text = getStatusText($status, $type);
    
    switch ($status) {
        case 'win':
        case 'success':
        case 'active':
        case 'completed':
            $class = 'success';
            break;
        case 'pending':
            $class = 'warning';
            break;
        case 'lose':
        case 'failed':
        case 'blocked':
            $class = 'danger';
            break;
        default:
            $class = 'secondary';
    }
    
    return '<span class="badge bg-' . $class . '">' . $text . '</span>';
}

// ตรวจสอบว่างวดนี้ปิดรับซื้อแล้วหรือยัง
function isLotteryClosed($lottery_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT status, date FROM lotteries WHERE id = ?");
    $stmt->bind_param("i", $lottery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['status'] != 'pending') {
            return true;
        }
        
        // ถ้าวันที่หวยออกมาถึงแล้ว
        if (strtotime($row['date']) <= time()) {
            return true;
        }
    }
    
    return false;
}

// ดึงผลรางวัลล่าสุด
function getLatestLotteryResult() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM lotteries WHERE status = 'completed' ORDER BY date DESC LIMIT 1");
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// ดึงงวดหวยที่กำลังจะมาถึง
function getUpcomingLottery() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM lotteries WHERE status = 'pending' ORDER BY date ASC LIMIT 1");
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// ดึงอัตราการจ่ายรางวัลสำหรับงวดที่ระบุ
function getLotteryRates($lottery_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM rates WHERE lottery_id = ?");
    $stmt->bind_param("i", $lottery_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // หากไม่พบอัตราการจ่ายสำหรับงวดนี้ ใช้ค่าเริ่มต้น
    return [
        'first_prize' => 900.00,
        'front_three' => 500.00,
        'back_three' => 500.00,
        'back_two' => 90.00
    ];
}

// สร้างเลขอ้างอิงสำหรับการเติมเงิน
function generateDepositReference() {
    return 'DEP' . date('Ymd') . rand(1000, 9999);
}

// แปลงชื่อวิธีการชำระเงินเป็นภาษาไทย
function getPaymentMethodText($method) {
    $methods = [
        'bank' => 'โอนผ่านธนาคาร',
        'promptpay' => 'พร้อมเพย์',
        'truemoney' => 'ทรูมันนี่วอลเล็ท'
    ];
    
    return $methods[$method] ?? $method;
}

// ตรวจสอบว่าเป็น admin หรือไม่
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// สร้าง pagination
function createPagination($current_page, $total_pages, $url_pattern) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // ปุ่มก่อนหน้า
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="'.sprintf($url_pattern, $current_page - 1).'">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
    }
    
    // แสดงหน้า
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><a class="page-link" href="#">'.$i.'</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="'.sprintf($url_pattern, $i).'">'.$i.'</a></li>';
        }
    }
    
    // ปุ่มถัดไป
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="'.sprintf($url_pattern, $current_page + 1).'">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// แสดงข้อความแจ้งเตือน
function showAlert($message, $type = 'success') {
    if (!empty($message)) {
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// เช็คและแสดงข้อความแจ้งเตือนจาก session
function checkAndShowSessionAlert() {
    if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
        showAlert($_SESSION['message'], $_SESSION['message_type']);
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?>