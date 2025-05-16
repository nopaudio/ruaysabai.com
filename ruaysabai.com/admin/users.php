<?php
// เริ่มต้น session
session_start();

// ตรวจสอบว่าผู้ใช้ได้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// นำเข้าไฟล์การเชื่อมต่อฐานข้อมูล
require_once('../config.php');

// สร้างการเชื่อมต่อกับฐานข้อมูล
$conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password'], $db_config['database']);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// กำหนด charset เป็น utf8
$conn->set_charset($db_config['charset']);

// ตัวแปรสำหรับแสดงข้อความ
$message = '';
$messageType = '';

// จัดการกับการลบผู้ใช้
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    
    // ป้องกันการลบบัญชีแอดมินหลัก
    $checkAdmin = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
    $checkAdmin->bind_param("i", $deleteId);
    $checkAdmin->execute();
    $adminResult = $checkAdmin->get_result();
    $userData = $adminResult->fetch_assoc();
    
    if ($userData && ($userData['role'] === 'admin' && $userData['username'] === 'admin')) {
        $message = "ไม่สามารถลบบัญชีแอดมินหลักได้";
        $messageType = "danger";
    } else {
        // ลบผู้ใช้
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->bind_param("i", $deleteId);
        
        if ($deleteStmt->execute()) {
            $message = "ลบผู้ใช้เรียบร้อยแล้ว";
            $messageType = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการลบผู้ใช้: " . $conn->error;
            $messageType = "danger";
        }
        $deleteStmt->close();
    }
}

// จัดการกับการเพิ่มผู้ใช้ใหม่
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $lineId = trim($_POST['line_id']);
    
    // ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $message = "ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่อผู้ใช้อื่น";
        $messageType = "danger";
    } else if (empty($username) || empty($password)) {
        $message = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        $messageType = "danger";
    } else {
        // เข้ารหัสรหัสผ่าน
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // เพิ่มผู้ใช้ใหม่
        $insertStmt = $conn->prepare("INSERT INTO users (username, password, email, phone, role, line_id, balance, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $insertStmt->bind_param("ssssss", $username, $hashedPassword, $email, $phone, $role, $lineId);
        
        if ($insertStmt->execute()) {
            $message = "เพิ่มผู้ใช้ใหม่เรียบร้อยแล้ว";
            $messageType = "success";
        } else {
            $message = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้: " . $conn->error;
            $messageType = "danger";
        }
        $insertStmt->close();
    }
    $checkStmt->close();
}

// จัดการกับการแก้ไขผู้ใช้
if (isset($_POST['edit_user'])) {
    $userId = $_POST['user_id'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $lineId = trim($_POST['line_id']);
    $balance = floatval($_POST['balance']);
    
    // ตรวจสอบว่าเป็นแอดมินหลักหรือไม่
    $checkAdmin = $conn->prepare("SELECT role, username FROM users WHERE id = ?");
    $checkAdmin->bind_param("i", $userId);
    $checkAdmin->execute();
    $adminResult = $checkAdmin->get_result();
    $userData = $adminResult->fetch_assoc();
    
    $updateSQL = "UPDATE users SET email = ?, phone = ?, line_id = ?, balance = ?";
    $params = array($email, $phone, $lineId, $balance);
    $types = "sssd";
    
    // ตรวจสอบและป้องกันการเปลี่ยนแปลงสิทธิ์ของแอดมินหลัก
    if ($userData && ($userData['role'] === 'admin' && $userData['username'] === 'admin') && $role !== 'admin') {
        $message = "ไม่สามารถเปลี่ยนสิทธิ์ของบัญชีแอดมินหลักได้";
        $messageType = "danger";
    } else {
        $updateSQL .= ", role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    // อัปเดตรหัสผ่านถ้ามีการกรอก
    if (!empty($_POST['password'])) {
        $password = trim($_POST['password']);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateSQL .= ", password = ?";
        $params[] = $hashedPassword;
        $types .= "s";
    }
    
    $updateSQL .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    $updateStmt = $conn->prepare($updateSQL);
    $updateStmt->bind_param($types, ...$params);
    
    if ($updateStmt->execute()) {
        $message = "อัปเดตข้อมูลผู้ใช้เรียบร้อยแล้ว";
        $messageType = "success";
    } else {
        $message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูลผู้ใช้: " . $conn->error;
        $messageType = "danger";
    }
    $updateStmt->close();
}

// ดึงข้อมูลผู้ใช้ทั้งหมดสำหรับแสดงในตาราง
$sql = "SELECT * FROM users ORDER BY username";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบหลังบ้าน</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap');
        
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
        }
        
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        
        .btn-custom {
            border-radius: 5px;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="content">
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-12">
                        <h2><i class="fas fa-users"></i> จัดการผู้ใช้งาน</h2>
                        <hr>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">รายชื่อผู้ใช้งานทั้งหมด</h5>
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal">
                                    <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ชื่อผู้ใช้</th>
                                                <th>อีเมล</th>
                                                <th>เบอร์โทร</th>
                                                <th>Line ID</th>
                                                <th>บทบาท</th>
                                                <th>ยอดเงิน</th>
                                                <th>วันที่สมัคร</th>
                                                <th>จัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result->num_rows > 0): ?>
                                                <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['line_id']); ?></td>
                                                    <td>
                                                        <?php if ($row['role'] == 'admin'): ?>
                                                            <span class="badge badge-danger">ผู้ดูแลระบบ</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-primary">สมาชิก</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($row['balance'], 2); ?> บาท</td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm edit-btn" 
                                                                data-toggle="modal" 
                                                                data-target="#editUserModal"
                                                                data-id="<?php echo $row['id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                                                data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                                                                data-line="<?php echo htmlspecialchars($row['line_id']); ?>"
                                                                data-role="<?php echo htmlspecialchars($row['role']); ?>"
                                                                data-balance="<?php echo htmlspecialchars($row['balance']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        
                                                        <?php if ($row['username'] !== 'admin'): ?>
                                                        <a href="users.php?delete=<?php echo $row['id']; ?>" 
                                                           class="btn btn-danger btn-sm delete-btn"
                                                           onclick="return confirm('คุณต้องการลบผู้ใช้นี้หรือไม่?');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">ไม่พบข้อมูลผู้ใช้</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal เพิ่มผู้ใช้ใหม่ -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addUserModalLabel">เพิ่มผู้ใช้ใหม่</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="users.php" method="post">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="email">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="phone">เบอร์โทร</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="line_id">Line ID</label>
                            <input type="text" class="form-control" id="line_id" name="line_id">
                        </div>
                        <div class="form-group">
                            <label for="role">บทบาท</label>
                            <select class="form-control" id="role" name="role">
                                <option value="user">สมาชิก</option>
                                <option value="admin">ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_user" class="btn btn-success">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal แก้ไขผู้ใช้ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editUserModalLabel">แก้ไขข้อมูลผู้ใช้</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="users.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group">
                            <label>ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">รหัสผ่าน (เว้นว่างหากไม่ต้องการเปลี่ยน)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="edit_email">อีเมล</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">เบอร์โทร</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="edit_line_id">Line ID</label>
                            <input type="text" class="form-control" id="edit_line_id" name="line_id">
                        </div>
                        <div class="form-group">
                            <label for="edit_role">บทบาท</label>
                            <select class="form-control" id="edit_role" name="role">
                                <option value="user">สมาชิก</option>
                                <option value="admin">ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_balance">ยอดเงินคงเหลือ (บาท)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_balance" name="balance">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // เซ็ตค่าสำหรับ Modal แก้ไขผู้ใช้
            $('.edit-btn').click(function() {
                const id = $(this).data('id');
                const username = $(this).data('username');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const line = $(this).data('line');
                const role = $(this).data('role');
                const balance = $(this).data('balance');
                
                $('#edit_user_id').val(id);
                $('#edit_username').val(username);
                $('#edit_email').val(email);
                $('#edit_phone').val(phone);
                $('#edit_line_id').val(line);
                $('#edit_role').val(role);
                $('#edit_balance').val(balance);
            });
            
            // ซ่อน alert หลังจาก 3 วินาที
            setTimeout(function() {
                $('.alert').alert('close');
            }, 3000);
        });
    </script>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>