<div class="sidebar bg-dark text-light" style="min-width: 250px; max-width: 250px; min-height: 100vh; position: sticky; top: 0;">
    <div class="sidebar-header p-3 text-center">
        <h3>ระบบหลังบ้าน</h3>
        <hr style="background-color: rgba(255, 255, 255, 0.2);">
    </div>
    <div class="sidebar-profile text-center mb-4">
        <div class="profile-avatar mb-2">
            <i class="fas fa-user-circle fa-4x"></i>
        </div>
        <div class="profile-info">
            <h5><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'ผู้ดูแลระบบ'; ?></h5>
            <span class="badge badge-success">ผู้ดูแลระบบ</span>
        </div>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt mr-2"></i> แดชบอร์ด
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active bg-primary' : ''; ?>" href="users.php">
                <i class="fas fa-users mr-2"></i> จัดการผู้ใช้งาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'lottery-results.php' ? 'active bg-primary' : ''; ?>" href="lottery-results.php">
                <i class="fas fa-clipboard-list mr-2"></i> จัดการผลหวย
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'lottery-settings.php' ? 'active bg-primary' : ''; ?>" href="lottery-settings.php">
                <i class="fas fa-sliders-h mr-2"></i> ตั้งค่าอัตราจ่าย
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'deposits.php' ? 'active bg-primary' : ''; ?>" href="deposits.php">
                <i class="fas fa-money-check-alt mr-2"></i> อนุมัติการเติมเงิน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active bg-primary' : ''; ?>" href="reports.php">
                <i class="fas fa-chart-bar mr-2"></i> รายงานสถิติ
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active bg-primary' : ''; ?>" href="settings.php">
                <i class="fas fa-cogs mr-2"></i> ตั้งค่าระบบ
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer p-3" style="position: absolute; bottom: 0; width: 100%;">
        <hr style="background-color: rgba(255, 255, 255, 0.2);">
        <a href="../index.php" class="btn btn-outline-light btn-sm mr-2" target="_blank">
            <i class="fas fa-home"></i> หน้าแรก
        </a>
        <a href="logout.php" class="btn btn-danger btn-sm">
            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
        </a>
    </div>
</div>

<style>
    .nav-link {
        padding: 10px 15px;
        border-radius: 5px;
        margin-bottom: 5px;
        transition: all 0.3s;
    }
    
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .active {
        font-weight: bold;
    }
    
    .sidebar {
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }
</style>