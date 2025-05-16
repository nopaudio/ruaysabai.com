<!-- Sidebar -->
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">หลัก</div>
                <a class="nav-link" href="index.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    แดชบอร์ด
                </a>
                
                <div class="sb-sidenav-menu-heading">การจัดการระบบ</div>
                
                <a class="nav-link" href="manage_users.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                    จัดการผู้ใช้งาน
                </a>
                
                <a class="nav-link" href="lottery_results.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-trophy"></i></div>
                    จัดการผลรางวัล
                </a>
                
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTransactions">
                    <div class="sb-nav-link-icon"><i class="fas fa-money-bill-wave"></i></div>
                    การเงิน
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseTransactions">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="deposits.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-down"></i></div>
                            การเติมเงิน
                        </a>
                        <a class="nav-link" href="payments.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-up"></i></div>
                            การจ่ายเงินรางวัล
                        </a>
                        <a class="nav-link" href="transactions.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt"></i></div>
                            ธุรกรรมทั้งหมด
                        </a>
                    </nav>
                </div>
                
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSettings">
                    <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                    ตั้งค่าระบบ
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseSettings">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="payout_settings.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calculator"></i></div>
                            ตั้งค่าอัตราจ่าย
                        </a>
                        <a class="nav-link" href="general_settings.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-sliders-h"></i></div>
                            ตั้งค่าทั่วไป
                        </a>
                    </nav>
                </div>
                
                <div class="sb-sidenav-menu-heading">รายงาน</div>
                <a class="nav-link" href="reports.php">
                    <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                    รายงานสรุป
                </a>
            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">ล็อกอินเป็น:</div>
            <?php echo $_SESSION['admin_username']; ?>
        </div>
    </nav>
</div>