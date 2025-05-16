                                <div class="stats-number text-success"><?php echo number_format($completed_missions); ?></div>
                                <p class="stats-label">ภารกิจที่สำเร็จ</p>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <?php if (count($active_missions) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">ภารกิจที่กำลังทำ <?php echo count($active_missions); ?> รายการ</p>
                            <a href="missions.php" class="small text-success">ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-warning"><?php echo number_format($redeemed_rewards); ?></div>
                                <p class="stats-label">รางวัลที่แลก</p>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <?php if (count($reward_orders) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">รางวัลล่าสุด: <?php echo htmlspecialchars($reward_orders[0]['name'] ?? 'ไม่มี'); ?></p>
                            <a href="reward_history.php" class="small text-warning">ดูประวัติ <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number"><?php echo $level; ?></div>
                                <p class="stats-label">ระดับสมาชิก</p>
                            </div>
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">ความก้าวหน้า</span>
                                <span class="small"><?php echo number_format(100 - $level_progress, 1); ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $level_progress; ?>%" aria-valuenow="<?php echo $level_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Row -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Daily Mission Card -->
                <?php if ($daily_mission): ?>
                <div class="card dashboard-card daily-mission-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title fw-bold mb-3"><i class="fas fa-bolt me-2"></i> ภารกิจประจำวัน</h5>
                                <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($daily_mission['name']); ?></h4>
                                <p class="mb-3"><?php echo htmlspecialchars($daily_mission['description']); ?></p>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-coins me-1"></i> <?php echo number_format($daily_mission['points']); ?> แต้ม</span>
                                    <a href="mission_detail.php?id=<?php echo $daily_mission['id']; ?>" class="btn btn-light btn-sm btn-modern">
                                        <i class="fas fa-play me-1"></i> เริ่มภารกิจ
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($daily_mission['image_url'])): ?>
                            <div class="ms-3 d-none d-md-block">
                                <img src="<?php echo htmlspecialchars($daily_mission['image_url']); ?>" alt="ภารกิจประจำวัน" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Point History Chart -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> การได้รับแต้ม 7 วันล่าสุด</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="pointChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Active Missions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> ภารกิจที่กำลังทำ</h5>
                            <a href="missions.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($active_missions) > 0): ?>
                            <?php foreach ($active_missions as $mission): ?>
                            <div id="mission-<?php echo $mission['id']; ?>" class="d-flex align-items-center justify-content-between mb-3 p-3 rounded border">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($mission['name']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($mission['description']); ?></p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary mb-2 d-block"><?php echo number_format($mission['points']); ?> แต้ม</span>
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $mission['progress']; ?>%" aria-valuenow="<?php echo $mission['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted progress-text"><?php echo $mission['progress']; ?>% เสร็จสิ้น</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <img src="assets/images/empty-state.svg" alt="ไม่มีภารกิจ" class="mb-3" style="width: 120px; height: 120px;" onerror="this.src='assets/images/empty-placeholder.png'">
                                <p class="text-muted">คุณยังไม่มีภารกิจที่กำลังทำอยู่</p>
                                <a href="missions.php" class="btn btn-primary btn-sm btn-modern">
                                    <i class="fas fa-search me-1"></i> ค้นหาภารกิจใหม่
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> ประวัติการทำรายการล่าสุด</h5>
                            <a href="transaction_history.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 fw-bold"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></small>
                                </div>
                                <span class="badge <?php echo $transaction['points'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $transaction['points'] >= 0 ? '+' : ''; ?><?php echo number_format($transaction['points']); ?> แต้ม
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted">ยังไม่มีประวัติการทำรายการ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($transactions) > 0): ?>
                    <div class="card-footer text-center">
                        <a href="transaction_history.php" class="text-primary">ดูประวัติทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- User Profile Card -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i> โปรไฟล์ของฉัน</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="rounded-circle" width="100" height="100">
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username'] ?? ''); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        <?php if (isVIP()): ?>
                            <span class="badge bg-warning text-dark mb-3"><i class="fas fa-crown me-1"></i> สมาชิก VIP</span>
                        <?php endif; ?>
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-outline-primary btn-sm btn-modern">
                                <i class="fas fa-edit me-1"></i> แก้ไขโปรไฟล์
                            </a>
                            <?php if (!isVIP()): ?>
                            <a href="vip.php" class="btn btn-warning btn-sm btn-modern">
                                <i class="fas fa-crown me-1"></i> อัพเกรดเป็น VIP
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i> การแจ้งเตือนล่าสุด</h5>
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="d-flex">
                                    <?php 
                                    $icon_class = 'info';
                                    
                                    switch ($notification['action']) {
                                        case 'mission_complete':
                                            $icon_class = 'check-circle text-success';
                                            break;
                                        case 'reward_redeemed':
                                            $icon_class = 'gift text-warning';
                                            break;
                                        case 'points_earned':
                                            $icon_class = 'coins text-primary';
                                            break;
                                        case 'level_up':
                                            $icon_class = 'level-up-alt text-info';
                                            break;
                                    }
                                    ?>
                                    <div class="me-3">
                                        <i class="fas fa-<?php echo $icon_class; ?> fa-lg"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['description']); ?></p>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted">ไม่มีการแจ้งเตือนใหม่</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($notifications) > 0): ?>
                    <div class="card-footer text-center">
                        <a href="notifications.php" class="text-primary">ดูการแจ้งเตือนทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Reward Orders -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i> รางวัลที่แลกล่าสุด</h5>
                            <a href="reward_history.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($reward_orders) > 0): ?>
                            <?php foreach ($reward_orders as $order): ?>
                            <div class="reward-item d-flex align-items-center">
                                <?php if (!empty($order['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($order['image_url']); ?>" alt="<?php echo htmlspecialchars($order['name']); ?>" class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                <div class="me-3 rounded bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fas fa-gift text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['name']); ?></h6>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></small>
                                        <span class="badge <?php echo getStatusClass($order['status']); ?>">
                                            <?php echo translateStatus($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted">คุณยังไม่เคยแลกของรางวัล</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($reward_orders) > 0): ?>
                    <div class="card-footer text-center">
                        <a href="reward_history.php" class="text-primary">ดูประวัติการแลกรางวัลทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Recommended Rewards -->
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-gift me-2"></i> รางวัลแนะนำ</h5>
                            <a href="rewards.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (count($recommended_rewards) > 0): ?>
                                <?php foreach ($recommended_rewards as $reward): ?>
                                <div class="col-6 mb-3">
                                    <div class="card reward-card h-100">
                                        <?php if (!empty($reward['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($reward['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($reward['name']); ?>">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 100px;">
                                            <i class="fas fa-gift fa-2x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div class="card-body p-2">
                                            <h6 class="card-title"><?php echo htmlspecialchars($reward['name']); ?></h6>
                                            <p class="badge bg-primary mb-2"><?php echo number_format($reward['points_required']); ?> แต้ม</p>
                                            <a href="reward_detail.php?id=<?php echo $reward['id']; ?>" class="btn btn-sm btn-outline-primary w-100">ดูรายละเอียด</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p class="text-muted">ไม่มีรางวัลแนะนำในขณะนี้</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($site_name); ?></h5>
                    <p class="mb-3">ระบบสมาชิกสะสมแต้มและแลกของรางวัลที่ให้คุณสนุกกับการเก็บแต้มและรับสิทธิพิเศษมากมาย</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mt-4 mt-md-0 mb-4 mb-md-0">
                    <h6 class="fw-bold mb-3">เมนูหลัก</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">หน้าแรก</a>
                        <a href="missions.php">ภารกิจ</a>
                        <a href="rewards.php">รางวัล</a>
                        <a href="profile.php">โปรไฟล์</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mt-4 mt-md-0 mb-4 mb-md-0">
                    <h6 class="fw-bold mb-3">ช่วยเหลือ</h6>
                    <div class="footer-links">
                        <a href="faq.php">คำถามที่พบบ่อย</a>
                        <a href="terms.php">เงื่อนไขการใช้งาน</a>
                        <a href="privacy.php">นโยบายความเป็นส่วนตัว</a>
                        <a href="contact.php">ติดต่อเรา</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mt-4 mt-lg-0">
                    <h6 class="fw-bold mb-3">ติดต่อเรา</h6>
                    <div class="mb-3">
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> 123 ถนนสุขุมวิท แขวงคลองเตย เขตวัฒนา กรุงเทพฯ 10110</p>
                        <p class="mb-1"><i class="fas fa-phone-alt me-2"></i> 02-123-4567</p>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i> support@ruaysabai.com</p>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($site_name); ?>. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <!-- Toast Container for Notifications -->
    <div class="toast-container"></div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart.js - Point History
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('pointChart').getContext('2d');
            
            // สร้างข้อมูลสำหรับแสดงกราฟ
            <?php
            // นำข้อมูลมาใส่ในตัวแปร JavaScript
            echo "const last7Days = " . json_encode(array_values($last7Days)) . ";\n";
            echo "const pointsData = " . json_encode(array_values($pointsData)) . ";\n";
            ?>
            
            // สร้างกราฟ
            const pointChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [{
                        label: 'แต้มที่ได้รับ',
                        data: pointsData,
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    family: "'Prompt', 'Kanit', sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Prompt', 'Kanit', sans-serif"
                                }
                            }
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        });

        // ฟังก์ชันอัพเดตความก้าวหน้าภารกิจ
        function updateMissionProgress(missionId, progress) {
            fetch('ajax/update_mission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mission_id=${missionId}&progress=${progress}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // อัพเดต UI
                    document.querySelector(`#mission-${missionId} .progress-bar`).style.width = `${progress}%`;
                    document.querySelector(`#mission-${missionId} .progress-text`).textContent = `${progress}% เสร็จสิ้น`;
                    
                    // ถ้าเสร็จสมบูรณ์
                    if (progress >= 100) {
                        showCompletionAlert(missionId, data.points);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // แสดงการแจ้งเตือนเมื่อทำภารกิจเสร็จ
        function showCompletionAlert(missionId, points) {
            const toast = `
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto"><i class="fas fa-check-circle me-2"></i> ภารกิจสำเร็จ!</strong>
                    <small>เมื่อสักครู่</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    คุณได้รับ ${points} แต้มจากการทำภารกิจนี้สำเร็จ!
                </div>
            </div>
            `;
            
            const toastContainer = document.querySelector('.toast-container');
            toastContainer.innerHTML = toast + toastContainer.innerHTML;
            
            // อัพเดตคะแนนรวม
            const currentPoints = parseInt(document.querySelector('.stats-number.text-primary').textContent.replace(/,/g, ''));
            const newPoints = currentPoints + points;
            document.querySelector('.stats-number.text-primary').textContent = newPoints.toLocaleString();
        }
    </script>
</body>
</html><?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
require_once 'config.php';

// ตรวจสอบการล็อกอิน
requireLogin();

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// ตั้งค่าคะแนนสำหรับแต่ละระดับ
$points_per_level = getSetting('points_per_level', 1000);

// บันทึกการเข้าใช้งาน Dashboard
addLog('view_dashboard', 'ผู้ใช้เข้าดูหน้า Dashboard');

// ดึงจำนวนแต้มทั้งหมดของผู้ใช้
$total_points = $user['points'] ?? 0;

// คำนวณระดับผู้ใช้งาน
$level = floor($total_points / $points_per_level) + 1;
$points_to_next_level = $points_per_level - ($total_points % $points_per_level);
$level_progress = (($total_points % $points_per_level) / $points_per_level) * 100;

// ดึงภารกิจที่กำลังทำ
$stmt = $db->prepare("
    SELECT m.id, m.name, m.description, m.points, um.progress 
    FROM missions m 
    JOIN user_missions um ON m.id = um.mission_id 
    WHERE um.user_id = :user_id AND um.status = 'in_progress' 
    ORDER BY um.updated_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$active_missions = $stmt->fetchAll();

// นับจำนวนภารกิจที่ทำสำเร็จ
$stmt = $db->prepare("
    SELECT COUNT(*) as completed_missions 
    FROM user_missions 
    WHERE user_id = :user_id AND status = 'completed'
");
$stmt->execute(['user_id' => $user_id]);
$mission_data = $stmt->fetch();
$completed_missions = $mission_data['completed_missions'] ?? 0;

// นับจำนวนรางวัลที่แลก
$stmt = $db->prepare("
    SELECT COUNT(*) as redeemed_rewards 
    FROM reward_orders 
    WHERE user_id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$reward_data = $stmt->fetch();
$redeemed_rewards = $reward_data['redeemed_rewards'] ?? 0;

// ดึงประวัติการทำรายการล่าสุด
$stmt = $db->prepare("
    SELECT id, points, transaction_type, description, created_at 
    FROM point_transactions 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute(['user_id' => $user_id]);
$transactions = $stmt->fetchAll();

// ดึงของรางวัลแนะนำ
$stmt = $db->prepare("
    SELECT id, name, description, points_required, image_url 
    FROM rewards 
    WHERE active = 1 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute();
$recommended_rewards = $stmt->fetchAll();

// ดึงประวัติการแลกของรางวัล
$stmt = $db->prepare("
    SELECT ro.id, r.name, r.image_url, ro.status, ro.created_at 
    FROM reward_orders ro 
    JOIN rewards r ON ro.reward_id = r.id 
    WHERE ro.user_id = :user_id 
    ORDER BY ro.created_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$reward_orders = $stmt->fetchAll();

// ดึงการแจ้งเตือนล่าสุด
$stmt = $db->prepare("
    SELECT id, action, description, created_at 
    FROM notifications 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$notifications = $stmt->fetchAll();

// ดึงข้อมูลสำหรับกราฟคะแนน 7 วันล่าสุด
$point_history = [];
$stmt = $db->prepare("
    SELECT DATE(created_at) as date, SUM(points) as daily_points 
    FROM point_transactions 
    WHERE user_id = :user_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$stmt->execute(['user_id' => $user_id]);
$db_point_history = $stmt->fetchAll();

// สร้างอาร์เรย์สำหรับแสดงข้อมูล 7 วันล่าสุด
$last7Days = [];
$pointsData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last7Days[] = date('d/m', strtotime($date));
    $pointsData[$date] = 0;
}

// เติมข้อมูลคะแนนสำหรับวันที่มีข้อมูล
foreach ($db_point_history as $record) {
    $pointsData[$record['date']] = (int)$record['daily_points'];
}

// ข้อความต้อนรับ
$welcome_message = '';
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in']) {
    $time_of_day = '';
    $current_hour = date('H');
    
    if ($current_hour < 12) {
        $time_of_day = 'สวัสดีตอนเช้า';
    } elseif ($current_hour < 17) {
        $time_of_day = 'สวัสดีตอนบ่าย';
    } else {
        $time_of_day = 'สวัสดีตอนเย็น';
    }
    
    $welcome_message = $time_of_day . ' คุณ' . htmlspecialchars($user['username'] ?? '') . ' ยินดีต้อนรับกลับมา!';
    
    // เคลียร์ค่า flag หลังจากแสดงข้อความแล้ว
    $_SESSION['just_logged_in'] = false;
}

// ดึงภารกิจประจำวัน
$stmt = $db->prepare("
    SELECT m.id, m.name, m.description, m.points, m.image_url 
    FROM missions m 
    WHERE m.is_daily = 1 AND m.active = 1 
    AND NOT EXISTS (
        SELECT 1 FROM user_missions um 
        WHERE um.mission_id = m.id AND um.user_id = :user_id 
        AND DATE(um.completed_at) = CURDATE() AND um.status = 'completed'
    ) 
    ORDER BY RAND() 
    LIMIT 1
");
$stmt->execute(['user_id' => $user_id]);
$daily_mission = $stmt->fetch();

// ฟังก์ชันแปลงสถานะเป็นภาษาไทย
function translateStatus($status) {
    $statusMap = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'completed' => 'สำเร็จแล้ว',
        'cancelled' => 'ยกเลิกแล้ว'
    ];
    
    return $statusMap[$status] ?? $status;
}

// ฟังก์ชันกำหนด class สำหรับแสดงสถานะ
function getStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    
    return $classMap[$status] ?? 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด | <?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Prompt, Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Prompt', 'Kanit', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }
        
        .navbar {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            padding: 0.7rem 1rem;
            transition: all 0.3s ease;
        }
        
        .navbar .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.active {
            color: white !important;
            border-bottom: 3px solid white;
        }
        
        .navbar .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            animation: dropdownAnimation 0.3s ease forwards;
        }
        
        @keyframes dropdownAnimation {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fc;
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 0.7rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            border-top-left-radius: 0.7rem !important;
            border-top-right-radius: 0.7rem !important;
            font-weight: 700;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background-color: transparent;
            padding: 1rem 1.5rem;
        }
        
        .stats-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
            height: 100%;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #858796;
            margin-bottom: 0;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
            margin: 0.5rem 0;
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        .daily-mission-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            color: white;
            position: relative;
        }
        
        .daily-mission-card .card-body {
            position: relative;
            z-index: 1;
        }
        
        .daily-mission-card:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .transaction-item, .notification-item, .reward-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .transaction-item:last-child, .notification-item:last-child, .reward-item:last-child {
            border-bottom: none;
        }
        
        .transaction-item:hover, .notification-item:hover, .reward-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .reward-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.3rem 0.8rem rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2);
        }
        
        .reward-card img {
            height: 180px;
            object-fit: cover;
        }
        
        .reward-card .card-body {
            padding: 1.25rem;
        }
        
        .reward-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            height: 2.5rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #FFA500, #FF8C00);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 165, 0, 0.4);
        }
        
        .btn-modern {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-modern:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        
        .btn-modern:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: -1;
        }
        
        .btn-modern:hover {
            color: white;
        }
        
        .btn-modern:hover:before {
            width: 100%;
        }
        
        footer {
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }
        
        footer .footer-links {
            display: flex;
            flex-direction: column;
        }
        
        footer .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        
        footer .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        footer .social-icons {
            display: flex;
            gap: 1rem;
        }
        
        footer .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        footer .social-icons a:hover {
            background-color: white;
            color: var(--dark-color);
            transform: translateY(-3px);
        }
        
        .badge {
            padding: 0.4em 0.7em;
            font-weight: 500;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .level-badge {
            background: linear-gradient(45deg, #3a1c71, #d76d77, #ffaf7b);
            color: white;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .reward-card img {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="<?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?>" onerror="this.src='assets/images/logo-placeholder.png'">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> หน้าแรก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="missions.php">
                            <i class="fas fa-tasks me-1"></i> ภารกิจ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rewards.php">
                            <i class="fas fa-gift me-1"></i> ของรางวัล
                        </a>
                    </li>
                    <?php if (!isVIP()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="vip.php">
                            <i class="fas fa-crown me-1"></i> VIP
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                            <?php if (isVIP()): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> VIP</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="transaction_history.php"><i class="fas fa-history me-2"></i>ประวัติการทำรายการ</a></li>
                            <li><a class="dropdown-item" href="reward_history.php"><i class="fas fa-shopping-bag me-2"></i>ประวัติแลกของรางวัล</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- แสดงข้อความแจ้งเตือนจาก session หากมี -->
        <?php showAlert(); ?>
        
        <!-- Toast Container for Notifications -->
        <div class="toast-container"></div>
        
        <!-- Welcome Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-2">สวัสดี, <?php echo htmlspecialchars($user['username'] ?? ''); ?>!</h2>
                <p class="text-muted mb-0">ยินดีต้อนรับกลับมาสู่แดชบอร์ดของคุณ</p>
            </div>
            <div class="d-flex align-items-center mt-3 mt-md-0">
                <div class="level-badge me-3">
                    <i class="fas fa-star"></i> ระดับ <?php echo $level; ?>
                </div>
                <div class="text-end">
                    <p class="mb-1 small text-muted">คะแนนสะสม</p>
                    <h4 class="mb-0 fw-bold text-primary"><?php echo number_format($total_points); ?> แต้ม</h4>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-primary"><?php echo number_format($total_points); ?></div>
                                <p class="stats-label">คะแนนสะสม</p>
                            </div>
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">ถึงระดับถัดไป</span>
                                <span class="small"><?php echo number_format($points_to_next_level); ?> แต้ม</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $level_progress; ?>%" aria-valuenow="<?php echo $level_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-success"><?php echo number_format($completed_missions); ?></div>
                                <p class="stats-label">ภารกิจที่สำเร็จ</p>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <?php if (count($active_missions) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">ภารกิจที่กำลังทำ <?php echo count($active_missions); ?> รายการ</p>
                            <a href="missions.php" class="small text-success">ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-warning"><?php echo number_format($redeemed_rewards); ?></div>
                                <p class="stats-label">รางวัลที่แลก</p>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <?php if (count($reward_orders) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">รางวัลล่าสุด: <?php echo htmlspecialchars($reward_orders[0]['name'] ?? 'ไม่มี'); ?></p>
                            <a href="reward_history.php" class="small text-warning">ดูประวัติ <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>    <footer class="bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="fw-bold mb-3"><?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?></h5>
                    <p class="mb-3">ระบบสมาชิกสะสมแต้มและแลกของรางวัลที่ให้คุณสนุกกับการเก็บแต้มและรับสิทธิพิเศษมากมาย</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mt-4 mt-md-0 mb-4 mb-md-0">
                    <h6 class="fw-bold mb-3">เมนูหลัก</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">หน้าแรก</a>
                        <a href="missions.php">ภารกิจ</a>
                        <a href="rewards.php">รางวัล</a>
                        <a href="profile.php">โปรไฟล์</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mt-4 mt-md-0 mb-4 mb-md-0">
                    <h6 class="fw-bold mb-3">ช่วยเหลือ</h6>
                    <div class="footer-links">
                        <a href="faq.php">คำถามที่พบบ่อย</a>
                        <a href="terms.php">เงื่อนไขการใช้งาน</a>
                        <a href="privacy.php">นโยบายความเป็นส่วนตัว</a>
                        <a href="contact.php">ติดต่อเรา</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 mt-4 mt-lg-0">
                    <h6 class="fw-bold mb-3">ติดต่อเรา</h6>
                    <div class="mb-3">
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i> 123 ถนนสุขุมวิท แขวงคลองเตย เขตวัฒนา กรุงเทพฯ 10110</p>
                        <p class="mb-1"><i class="fas fa-phone-alt me-2"></i> 02-123-4567</p>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i> support@<?php echo str_replace(['https://', 'http://'], '', SITE_URL); ?></p>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?>. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <!-- Toast Container for Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Chart.js - Point History
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('pointChart').getContext('2d');
            
            // สร้างข้อมูลสำหรับแสดงกราฟ
            <?php
            // นำข้อมูลมาใส่ในตัวแปร JavaScript
            echo "const last7Days = " . json_encode(array_values($last7Days)) . ";\n";
            echo "const pointsData = " . json_encode(array_values($pointsData)) . ";\n";
            ?>
            
            // สร้างกราฟ
            const pointChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [{
                        label: 'แต้มที่ได้รับ',
                        data: pointsData,
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    family: "'Prompt', 'Kanit', sans-serif"
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Prompt', 'Kanit', sans-serif"
                                }
                            }
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        });

        // ฟังก์ชันอัพเดตความก้าวหน้าภารกิจ
        function updateMissionProgress(missionId, progress) {
            fetch('ajax/update_mission.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mission_id=${missionId}&progress=${progress}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // อัพเดต UI
                    document.querySelector(`#mission-${missionId} .progress-bar`).style.width = `${progress}%`;
                    document.querySelector(`#mission-${missionId} .progress-text`).textContent = `${progress}% เสร็จสิ้น`;
                    
                    // ถ้าเสร็จสมบูรณ์
                    if (progress >= 100) {
                        showCompletionAlert(missionId, data.points);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // แสดงการแจ้งเตือนเมื่อทำภารกิจเสร็จ
        function showCompletionAlert(missionId, points) {
            const toast = `
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto"><i class="fas fa-check-circle me-2"></i> ภารกิจสำเร็จ!</strong>
                    <small>เมื่อสักครู่</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    คุณได้รับ ${points} แต้มจากการทำภารกิจนี้สำเร็จ!
                </div>
            </div>
            `;
            
            const toastContainer = document.querySelector('.toast-container');
            toastContainer.innerHTML = toast + toastContainer.innerHTML;
            
            // อัพเดตคะแนนรวม
            const currentPoints = parseInt(document.querySelector('.stats-number.text-primary').textContent.replace(/,/g, ''));
            const newPoints = currentPoints + points;
            document.querySelector('.stats-number.text-primary').textContent = newPoints.toLocaleString();
        }
    </script>
</body>
</html><?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
require_once 'config.php';

// ตรวจสอบการล็อกอิน
requireLogin();

// ดึงข้อมูลผู้ใช้
$user_id = $_SESSION['user_id'];
$user = getUserData($user_id);

// ตั้งค่าคะแนนสำหรับแต่ละระดับ
$points_per_level = getSetting('points_per_level', 1000);

// บันทึกการเข้าใช้งาน Dashboard
addLog('view_dashboard', 'ผู้ใช้เข้าดูหน้า Dashboard');

// ดึงจำนวนแต้มทั้งหมดของผู้ใช้
$total_points = $user['points'] ?? 0;

// คำนวณระดับผู้ใช้งาน
$level = floor($total_points / $points_per_level) + 1;
$points_to_next_level = $points_per_level - ($total_points % $points_per_level);
$level_progress = (($total_points % $points_per_level) / $points_per_level) * 100;

// ดึงภารกิจที่กำลังทำ
$stmt = $db->prepare("
    SELECT m.id, m.name, m.description, m.points, um.progress 
    FROM missions m 
    JOIN user_missions um ON m.id = um.mission_id 
    WHERE um.user_id = :user_id AND um.status = 'in_progress' 
    ORDER BY um.updated_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$active_missions = $stmt->fetchAll();

// นับจำนวนภารกิจที่ทำสำเร็จ
$stmt = $db->prepare("
    SELECT COUNT(*) as completed_missions 
    FROM user_missions 
    WHERE user_id = :user_id AND status = 'completed'
");
$stmt->execute(['user_id' => $user_id]);
$mission_data = $stmt->fetch();
$completed_missions = $mission_data['completed_missions'] ?? 0;

// นับจำนวนรางวัลที่แลก
$stmt = $db->prepare("
    SELECT COUNT(*) as redeemed_rewards 
    FROM reward_orders 
    WHERE user_id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$reward_data = $stmt->fetch();
$redeemed_rewards = $reward_data['redeemed_rewards'] ?? 0;

// ดึงประวัติการทำรายการล่าสุด
$stmt = $db->prepare("
    SELECT id, points, transaction_type, description, created_at 
    FROM point_transactions 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute(['user_id' => $user_id]);
$transactions = $stmt->fetchAll();

// ดึงของรางวัลแนะนำ
$stmt = $db->prepare("
    SELECT id, name, description, points_required, image_url 
    FROM rewards 
    WHERE active = 1 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute();
$recommended_rewards = $stmt->fetchAll();

// ดึงประวัติการแลกของรางวัล
$stmt = $db->prepare("
    SELECT ro.id, r.name, r.image_url, ro.status, ro.created_at 
    FROM reward_orders ro 
    JOIN rewards r ON ro.reward_id = r.id 
    WHERE ro.user_id = :user_id 
    ORDER BY ro.created_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$reward_orders = $stmt->fetchAll();

// ดึงการแจ้งเตือนล่าสุด
$stmt = $db->prepare("
    SELECT id, action, description, created_at 
    FROM notifications 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user_id]);
$notifications = $stmt->fetchAll();

// ดึงข้อมูลสำหรับกราฟคะแนน 7 วันล่าสุด
$point_history = [];
$stmt = $db->prepare("
    SELECT DATE(created_at) as date, SUM(points) as daily_points 
    FROM point_transactions 
    WHERE user_id = :user_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
");
$stmt->execute(['user_id' => $user_id]);
$db_point_history = $stmt->fetchAll();

// สร้างอาร์เรย์สำหรับแสดงข้อมูล 7 วันล่าสุด
$last7Days = [];
$pointsData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last7Days[] = date('d/m', strtotime($date));
    $pointsData[$date] = 0;
}

// เติมข้อมูลคะแนนสำหรับวันที่มีข้อมูล
foreach ($db_point_history as $record) {
    $pointsData[$record['date']] = (int)$record['daily_points'];
}

// ข้อความต้อนรับ
$welcome_message = '';
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in']) {
    $time_of_day = '';
    $current_hour = date('H');
    
    if ($current_hour < 12) {
        $time_of_day = 'สวัสดีตอนเช้า';
    } elseif ($current_hour < 17) {
        $time_of_day = 'สวัสดีตอนบ่าย';
    } else {
        $time_of_day = 'สวัสดีตอนเย็น';
    }
    
    $welcome_message = $time_of_day . ' คุณ' . htmlspecialchars($user['username'] ?? '') . ' ยินดีต้อนรับกลับมา!';
    
    // เคลียร์ค่า flag หลังจากแสดงข้อความแล้ว
    $_SESSION['just_logged_in'] = false;
}

// ดึงภารกิจประจำวัน
$stmt = $db->prepare("
    SELECT m.id, m.name, m.description, m.points, m.image_url 
    FROM missions m 
    WHERE m.is_daily = 1 AND m.active = 1 
    AND NOT EXISTS (
        SELECT 1 FROM user_missions um 
        WHERE um.mission_id = m.id AND um.user_id = :user_id 
        AND DATE(um.completed_at) = CURDATE() AND um.status = 'completed'
    ) 
    ORDER BY RAND() 
    LIMIT 1
");
$stmt->execute(['user_id' => $user_id]);
$daily_mission = $stmt->fetch();

// ฟังก์ชันแปลงสถานะเป็นภาษาไทย
function translateStatus($status) {
    $statusMap = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'completed' => 'สำเร็จแล้ว',
        'cancelled' => 'ยกเลิกแล้ว'
    ];
    
    return $statusMap[$status] ?? $status;
}

// ฟังก์ชันกำหนด class สำหรับแสดงสถานะ
function getStatusClass($status) {
    $classMap = [
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    
    return $classMap[$status] ?? 'bg-secondary';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด | <?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Prompt, Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6f42c1;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Prompt', 'Kanit', sans-serif;
            background-color: #f8f9fc;
            color: #5a5c69;
        }
        
        .navbar {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }
        
        .navbar-brand img {
            max-height: 40px;
        }
        
        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            padding: 0.7rem 1rem;
            transition: all 0.3s ease;
        }
        
        .navbar .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .navbar .nav-link.active {
            color: white !important;
            border-bottom: 3px solid white;
        }
        
        .navbar .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            animation: dropdownAnimation 0.3s ease forwards;
        }
        
        @keyframes dropdownAnimation {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 0.7rem 1.5rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fc;
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .main-content {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 0.7rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            border-top-left-radius: 0.7rem !important;
            border-top-right-radius: 0.7rem !important;
            font-weight: 700;
            border-bottom: none;
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            background-color: transparent;
            padding: 1rem 1.5rem;
        }
        
        .stats-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
            height: 100%;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #858796;
            margin-bottom: 0;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
            margin: 0.5rem 0;
        }
        
        .progress-bar {
            border-radius: 10px;
        }
        
        .daily-mission-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
            color: white;
            position: relative;
        }
        
        .daily-mission-card .card-body {
            position: relative;
            z-index: 1;
        }
        
        .daily-mission-card:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .transaction-item, .notification-item, .reward-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .transaction-item:last-child, .notification-item:last-child, .reward-item:last-child {
            border-bottom: none;
        }
        
        .transaction-item:hover, .notification-item:hover, .reward-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .reward-card {
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.3rem 0.8rem rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .reward-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2);
        }
        
        .reward-card img {
            height: 180px;
            object-fit: cover;
        }
        
        .reward-card .card-body {
            padding: 1.25rem;
        }
        
        .reward-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            height: 2.5rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #FFA500, #FF8C00);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(255, 165, 0, 0.4);
        }
        
        .btn-modern {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-modern:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }
        
        .btn-modern:before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: -1;
        }
        
        .btn-modern:hover {
            color: white;
        }
        
        .btn-modern:hover:before {
            width: 100%;
        }
        
        footer {
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }
        
        footer .footer-links {
            display: flex;
            flex-direction: column;
        }
        
        footer .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }
        
        footer .footer-links a:hover {
            color: white;
            transform: translateX(5px);
        }
        
        footer .social-icons {
            display: flex;
            gap: 1rem;
        }
        
        footer .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        footer .social-icons a:hover {
            background-color: white;
            color: var(--dark-color);
            transform: translateY(-3px);
        }
        
        .badge {
            padding: 0.4em 0.7em;
            font-weight: 500;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .level-badge {
            background: linear-gradient(45deg, #3a1c71, #d76d77, #ffaf7b);
            color: white;
            padding: 5px 10px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-number {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .reward-card img {
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="<?php echo htmlspecialchars(getSetting('site_name', 'ระบบสมาชิกสะสมแต้ม')); ?>" onerror="this.src='assets/images/logo-placeholder.png'">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-1"></i> หน้าแรก
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="missions.php">
                            <i class="fas fa-tasks me-1"></i> ภารกิจ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rewards.php">
                            <i class="fas fa-gift me-1"></i> ของรางวัล
                        </a>
                    </li>
                    <?php if (!isVIP()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="vip.php">
                            <i class="fas fa-crown me-1"></i> VIP
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['username'] ?? ''); ?>
                            <?php if (isVIP()): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> VIP</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="transaction_history.php"><i class="fas fa-history me-2"></i>ประวัติการทำรายการ</a></li>
                            <li><a class="dropdown-item" href="reward_history.php"><i class="fas fa-shopping-bag me-2"></i>ประวัติแลกของรางวัล</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- แสดงข้อความแจ้งเตือนจาก session หากมี -->
        <?php showAlert(); ?>
        
        <!-- Toast Container for Notifications -->
        <div class="toast-container"></div>
        
        <!-- Welcome Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-2">สวัสดี, <?php echo htmlspecialchars($user['username'] ?? ''); ?>!</h2>
                <p class="text-muted mb-0">ยินดีต้อนรับกลับมาสู่แดชบอร์ดของคุณ</p>
            </div>
            <div class="d-flex align-items-center mt-3 mt-md-0">
                <div class="level-badge me-3">
                    <i class="fas fa-star"></i> ระดับ <?php echo $level; ?>
                </div>
                <div class="text-end">
                    <p class="mb-1 small text-muted">คะแนนสะสม</p>
                    <h4 class="mb-0 fw-bold text-primary"><?php echo number_format($total_points); ?> แต้ม</h4>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-primary"><?php echo number_format($total_points); ?></div>
                                <p class="stats-label">คะแนนสะสม</p>
                            </div>
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">ถึงระดับถัดไป</span>
                                <span class="small"><?php echo number_format($points_to_next_level); ?> แต้ม</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $level_progress; ?>%" aria-valuenow="<?php echo $level_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-success"><?php echo number_format($completed_missions); ?></div>
                                <p class="stats-label">ภารกิจที่สำเร็จ</p>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <?php if (count($active_missions) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">ภารกิจที่กำลังทำ <?php echo count($active_missions); ?> รายการ</p>
                            <a href="missions.php" class="small text-success">ดูทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number text-warning"><?php echo number_format($redeemed_rewards); ?></div>
                                <p class="stats-label">รางวัลที่แลก</p>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <?php if (count($reward_orders) > 0): ?>
                        <div class="mt-3">
                            <p class="small mb-1">รางวัลล่าสุด: <?php echo htmlspecialchars($reward_orders[0]['name'] ?? 'ไม่มี'); ?></p>
                            <a href="reward_history.php" class="small text-warning">ดูประวัติ <i class="fas fa-arrow-right ms-1"></i></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stats-number"><?php echo $level; ?></div>
                                <p class="stats-label">ระดับสมาชิก</p>
                            </div>
                            <div class="stats-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">ความก้าวหน้า</span>
                                <span class="small"><?php echo number_format(100 - $level_progress, 1); ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $level_progress; ?>%" aria-valuenow="<?php echo $level_progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Row -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Welcome Message Toast -->
                <?php if (!empty($welcome_message)): ?>
                <div class="toast show mb-4" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <strong class="me-auto"><i class="fas fa-bell text-primary me-2"></i> การแจ้งเตือน</strong>
                        <small>เมื่อสักครู่</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        <?php echo $welcome_message; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Daily Mission Card -->
                <?php if ($daily_mission): ?>
                <div class="card dashboard-card daily-mission-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title fw-bold mb-3"><i class="fas fa-bolt me-2"></i> ภารกิจประจำวัน</h5>
                                <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($daily_mission['name']); ?></h4>
                                <p class="mb-3"><?php echo htmlspecialchars($daily_mission['description']); ?></p>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-coins me-1"></i> <?php echo number_format($daily_mission['points']); ?> แต้ม</span>
                                    <a href="mission_detail.php?id=<?php echo $daily_mission['id']; ?>" class="btn btn-light btn-sm btn-modern">
                                        <i class="fas fa-play me-1"></i> เริ่มภารกิจ
                                    </a>
                                </div>
                            </div>
                            <?php if (!empty($daily_mission['image_url'])): ?>
                            <div class="ms-3 d-none d-md-block">
                                <img src="<?php echo htmlspecialchars($daily_mission['image_url']); ?>" alt="ภารกิจประจำวัน" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Point History Chart -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> การได้รับแต้ม 7 วันล่าสุด</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="pointChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Active Missions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> ภารกิจที่กำลังทำ</h5>
                            <a href="missions.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($active_missions) > 0): ?>
                            <?php foreach ($active_missions as $mission): ?>
                            <div id="mission-<?php echo $mission['id']; ?>" class="d-flex align-items-center justify-content-between mb-3 p-3 rounded border">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($mission['name']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($mission['description']); ?></p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary mb-2 d-block"><?php echo number_format($mission['points']); ?> แต้ม</span>
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $mission['progress']; ?>%" aria-valuenow="<?php echo $mission['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted progress-text"><?php echo $mission['progress']; ?>% เสร็จสิ้น</small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <img src="assets/images/empty-state.svg" alt="ไม่มีภารกิจ" class="mb-3" style="width: 120px; height: 120px;" onerror="this.src='assets/images/empty-placeholder.png'">
                                <p class="text-muted">คุณยังไม่มีภารกิจที่กำลังทำอยู่</p>
                                <a href="missions.php" class="btn btn-primary btn-sm btn-modern">
                                    <i class="fas fa-search me-1"></i> ค้นหาภารกิจใหม่
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> ประวัติการทำรายการล่าสุด</h5>
                            <a href="transaction_history.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <div class="transaction-item d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-1 fw-bold"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></small>
                                </div>
                                <span class="badge <?php echo $transaction['points'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $transaction['points'] >= 0 ? '+' : ''; ?><?php echo number_format($transaction['points']); ?> แต้ม
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted">ยังไม่มีประวัติการทำรายการ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($transactions) > 0): ?>
                    <div class="card-footer text-center">
                        <a href="transaction_history.php" class="text-primary">ดูประวัติทั้งหมด <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- User Profile Card -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i> โปรไฟล์ของฉัน</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" class="rounded-circle" width="100" height="100">
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['username'] ?? ''); ?></h5>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        <?php if (isVIP()): ?>
                            <span class="badge bg-warning text-dark mb-3"><i class="fas fa-crown me-1"></i> สมาชิก VIP</span>
                        <?php endif; ?>
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-outline-primary btn-sm btn-modern">
                                <i class="fas fa-edit me-1"></i> แก้ไขโปรไฟล์
                            </a>
                            <?php if (!isVIP()): ?>
                            <a href="vip.php" class="btn btn-warning btn-sm btn-modern">
                                <i class="fas fa-crown me-1"></i> อัพเกรดเป็น VIP
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i> การแจ้งเตือนล่าสุด</h5>
                            <a href="notifications.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <div class="d-flex">
                                    <?php 
                                    $icon_class = 'info';
                                    
                                    switch ($notification['action']) {
                                        case 'mission_complete':
                                            $icon_class = 'check-circle text-success';
                                            break;
                                        case 'reward_redeemed':
                                            $icon_class = 'gift text-warning';
                                            break;
                                        case 'points_earned':
                                            $icon_class = 'coins text-primary';
                                            break;
                                        case 'level_up':
                                            $icon_class = 'level-up-alt text-info';
                                            break;
                                    }
                                    ?>