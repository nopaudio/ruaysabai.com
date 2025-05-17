<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host = 'localhost';
$db = 'xxvdoxxc_ruaysabai1';
$user = 'xxvdoxxc_ruaysabai1';
$pass = '0804441958';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ✅ ดึงค่ารอรับจาก settings
$claimTimeout = 60; // default 1 นาที
$timeoutResult = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'claim_ready_timeout'");
if ($timeoutResult && $timeoutResult->num_rows > 0) {
    $claimTimeout = intval($timeoutResult->fetch_assoc()['setting_value']);
}

// ตรวจสอบเวลาที่เหลือจาก session
if (isset($_SESSION['last_click_time'])) {
    $elapsed_time = time() - $_SESSION['last_click_time'];
    $remaining_time = 300 - $elapsed_time;
    $_SESSION['cooldown_left'] = max($remaining_time, 0);
} else {
    $_SESSION['cooldown_left'] = 300;
}

$cooldownLeft = $_SESSION['cooldown_left'];
if ($cooldownLeft === 0) {
    if (isset($_SESSION['ready_time'])) {
        $since_ready = time() - $_SESSION['ready_time'];
        if ($since_ready > $claimTimeout) {
            $_SESSION['cooldown_left'] = 300;
            unset($_SESSION['ready_time']);
        }
    } else {
        $_SESSION['ready_time'] = time();
    }
}

// ดึงข้อมูลยอดเงินสะสมล่าสุด
$sumResult = $conn->query("SELECT SUM(amount) as total FROM user_earnings WHERE user_id = $user_id");
$totalIncome = $sumResult->fetch_assoc()['total'] ?? 0;

// บันทึกเวลาเริ่มต้นถอยหลัง (ใน session)
if (isset($_POST['claim_income'])) {
    $current_time = time();
    $_SESSION['last_click_time'] = $current_time; // บันทึกเวลาใน session
    $_SESSION['cooldown_left'] = 300; // รีเซ็ตเวลาใหม่ทุกครั้งที่กด

    $amount = mt_rand(10, 100) / 100; // ยอดรายได้ที่สุ่ม
    $conn->query("INSERT INTO user_earnings (user_id, amount) VALUES ($user_id, $amount)");
    $_SESSION['last_income'] = $amount;

    echo json_encode([
        'success' => true,
        'amount' => $amount
    ]);
    exit;
}

// ตรวจสอบเวลาที่เหลือจาก session
if (isset($_SESSION['last_click_time'])) {
    $elapsed_time = time() - $_SESSION['last_click_time'];
    $remaining_time = 300 - $elapsed_time; // เวลาที่เหลือจากการนับถอยหลัง 5 นาที
    $_SESSION['cooldown_left'] = max($remaining_time, 0); // คำนวณเวลาที่เหลือ
} else {
    $_SESSION['cooldown_left'] = 300; // ถ้าไม่มีการคลิกเก็บไว้ ให้เริ่มต้นใหม่ 5 นาที
}
 


// ระดับสมาชิก
$levelMap = [
    'free' => ['minutes' => 5, 'min' => 5, 'max' => 15],
    'premium' => ['minutes' => 3, 'min' => 10, 'max' => 30],
    'vip' => ['minutes' => 1, 'min' => 15, 'max' => 50]
];
$level = 'free';
$res = $conn->query("SELECT level FROM users WHERE id = $user_id");
if ($res->num_rows > 0) {
    $level = $res->fetch_assoc()['level'] ?? 'free';
}
$settings = $levelMap[$level];

$refStats = $conn->query("SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN type = 'topup' THEN amount ELSE 0 END) as topup_total,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total
    FROM ref_commissions WHERE referrer_id = $user_id");

$refData = $refStats->fetch_assoc();
$refTotalCount = $refData['total_count'] ?? 0;
$refFromTopup = $refData['topup_total'] ?? 0;
$refFromIncome = $refData['income_total'] ?? 0;
$refTotal = $refFromTopup + $refFromIncome;


// Cooldown & รายได้ล่าสุด
$cooldownLeft = 0;
$lastEarn = 0;
$last = $conn->query("SELECT amount, earned_at FROM user_earnings WHERE user_id = $user_id ORDER BY earned_at DESC LIMIT 1");
if ($last->num_rows > 0) {
if ($cooldownLeft === 0) {
    if (isset($_SESSION['ready_time'])) {
        $since_ready = time() - $_SESSION['ready_time'];
        if ($since_ready > $claimTimeout) { // 3 นาที
		
            // รีเซ็ต cooldown และ session
            $cooldownLeft = $settings['minutes'] * 60;
            unset($_SESSION['ready_time']);
        }
    } else {
        $_SESSION['ready_time'] = time(); // ถ้ายังไม่เคยเก็บ
    }
}

    $last_data = $last->fetch_assoc();
    $last_time = new DateTime($last_data['earned_at']);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $last_time->getTimestamp();
    $cooldownSec = $settings['minutes'] * 60;
    if ($diff < $cooldownSec) {
        $cooldownLeft = $cooldownSec - $diff;
        $lastEarn = $last_data['amount'];
    }
}

// รายได้สะสม
$sumEarnings = $conn->query("SELECT SUM(amount) as total FROM user_earnings WHERE user_id = $user_id");
$sumBonus = $conn->query("SELECT SUM(amount) as total FROM user_bonus WHERE user_id = $user_id");

$totalEarn = $sumEarnings->fetch_assoc()['total'] ?? 0;
$totalBonus = $sumBonus->fetch_assoc()['total'] ?? 0;

$totalIncome = $totalEarn + $totalBonus;

// ค่าคอมมิชชัน
$ref = $conn->query("SELECT ref_by FROM users WHERE id = $user_id");
if ($ref->num_rows > 0) {
    $ref_by = $ref->fetch_assoc()['ref_by'];
    if (!empty($ref_by)) {
        // insert into ref_commissions
    }
}
$refStats = $conn->query("SELECT COUNT(*) as count, SUM(amount) as total FROM ref_commissions WHERE referrer_id = $user_id");
$refData = $refStats->fetch_assoc();
$refCount = $refData['count'] ?? 0;
$refTotal = $refData['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard | AISmartCash</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-white p-4 min-h-screen">
  <div class="max-w-6xl mx-auto">

    <div class="flex justify-between items-center mb-4">
      <h1 class="text-3xl font-bold">🎉 ยินดีต้อนรับ <?php echo $username; ?> (<?php echo strtoupper($level); ?>)</h1>
      <div class="text-sm text-green-400 flex items-center gap-1">
        <span class="relative flex h-3 w-3 mr-1">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
        ออนไลน์: <span id="onlineCount">25</span> คน
      </div>
    </div>

    <marquee id="marqueeBox" class="bg-black/30 rounded px-4 py-2 mb-4 text-lime-300 text-sm">
      📢 ระบบกำลังโหลดกิจกรรม...
    </marquee>
    
    <?php
$noticeFile = 'notice.txt';
if (file_exists($noticeFile)) {
  $noticeText = trim(file_get_contents($noticeFile));
  if (!empty($noticeText)) {
    $bgColor = ['from-yellow-100 to-yellow-200', 'from-orange-100 to-yellow-300', 'from-lime-100 to-green-200'];
    $picked = $bgColor[array_rand($bgColor)];
    echo "<div class='w-full mb-4'>
            <div class=\"bg-gradient-to-r {$picked} text-yellow-900 px-4 py-2 rounded shadow animate-pulse\">
              <marquee scrollamount='4'>📢 ประกาศ: " . htmlspecialchars($noticeText) . "</marquee>
            </div>
          </div>";
  }
}
?>


    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center mb-6">
      <a href="wallet.php" class="bg-blue-600 hover:bg-blue-700 p-4 rounded-xl shadow">💵<div class="mt-2 text-sm">เติมเงิน</div></a>
      <a href="withdraw.php" class="bg-green-600 hover:bg-green-700 p-4 rounded-xl shadow">🏦<div class="mt-2 text-sm">ถอนเงิน</div></a>
      <a href="bonus.php" class="bg-yellow-500 hover:bg-yellow-600 p-4 rounded-xl shadow">🎁<div class="mt-2 text-sm">โบนัส</div></a>
      <a href="history.php" class="bg-indigo-600 hover:bg-indigo-700 p-4 rounded-xl shadow">📜<div class="mt-2 text-sm">ประวัติ</div></a>
      <a href="logout.php" class="bg-red-600 hover:bg-red-700 p-4 rounded-xl shadow">🚪<div class="mt-2 text-sm">ออก</div></a>
    </div>


<div class="bg-white text-black p-6 rounded-xl shadow mb-6 text-center">
  <h2 class="text-xl font-semibold mb-2">💰 ยอดเงินที่สามารถถอนได้</h2>
  <p class="text-4xl font-bold text-green-600">฿<?php echo $totalIncome; ?></p>
</div>

 

<div class="bg-black/20 p-6 rounded-xl text-white text-center mb-6">
      <button id="claimBtn" onClick="claimIncome()" class="bg-lime-500 hover:bg-lime-600 px-6 py-3 rounded-full font-bold text-white shadow">
        💸 รับรายได้ตอนนี้
      </button>
      <div id="lastEarned" class="mt-3 text-green-400 text-lg hidden"></div>
      <div id="incomeStatus" class="mt-2 text-yellow-300 text-sm <?php echo $cooldownLeft > 0 ? '' : 'hidden'; ?>">
        ⏳ กรุณารออีก <span id="cooldown"><?php echo $cooldownLeft; ?></span> วินาที
      </div>
      <div id="readyMsg" class="mt-2 text-green-400 text-sm hidden">
        ✅ กรุณากดรับรายได้ภายใน <?php echo $claimTimeout / 60; ?> นาที ไม่งั้นเวลาจะนับใหม่
      </div>
    </div>
  </div>
<script>
let cooldown = <?php echo $cooldownLeft; ?>;
let readyExpired = <?php echo isset($_SESSION['ready_time']) && (time() - $_SESSION['ready_time']) > $claimTimeout ? 'true' : 'false'; ?>;
let timer = null;

function startCooldown() {
  document.getElementById('claimBtn').disabled = true;
  document.getElementById('claimBtn').classList.add('opacity-50');
  document.getElementById('incomeStatus').classList.remove('hidden');
  timer = setInterval(() => {
    cooldown--;
    document.getElementById('cooldown').innerText = cooldown;
    if (cooldown <= 0) {
      clearInterval(timer);
      document.getElementById('incomeStatus').classList.add('hidden');
      document.getElementById('claimBtn').disabled = false;
      document.getElementById('claimBtn').classList.remove('opacity-50');
      document.getElementById('readyMsg').classList.remove('hidden');
      fetch('save_ready_time.php', { method: 'POST' });
    }
  }, 1000);
}

function claimIncome() {
  if (cooldown > 0) return;
  fetch('income.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('lastEarned').innerText = `✅ คุณได้รับรายได้ +${data.amount} บาท`;
        document.getElementById('lastEarned').classList.remove('hidden');
        document.getElementById('readyMsg').classList.add('hidden');
        cooldown = 300;
        startCooldown();
      }
    });
}

window.onload = () => {
  if (cooldown > 0) {
    startCooldown();
  } else {
    document.getElementById('claimBtn').disabled = false;
    document.getElementById('claimBtn').classList.remove('opacity-50');
    document.getElementById('readyMsg').classList.remove('hidden');
  }

  if (readyExpired) {
    document.getElementById('claimBtn').disabled = true;
    document.getElementById('claimBtn').classList.add('opacity-50');
    document.getElementById('readyMsg').classList.add('hidden');
    document.getElementById('incomeStatus').classList.remove('hidden');
    document.getElementById('cooldown').innerText = 300;
    cooldown = 300;
    startCooldown();
  }
};
</script>


      
      
      
<!--- <div class="text-center mt-8">
  <button id="aiClickBtn" onClick="clickAI()"
    class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-8 py-4 rounded-full font-bold text-xl shadow-xl hover:scale-105 transition">
    🤖 แตะเพื่อให้ AI สร้างรายได้
  </button>
  <div id="aiStatus" class="mt-3 text-green-400 font-semibold text-lg hidden"></div>
  <div id="shopeeBox" class="mt-3 hidden">
    <a id="shopeeLink" href="#" target="_blank" class="underline text-blue-300 hover:text-blue-400 font-bold text-sm"></a>
  </div>
</div> ---->

<script>
function clickAI() {
  fetch('ai_click.php')
    .then(res => res.json())
    .then(data => {
      const status = document.getElementById('aiStatus');
      if (data.success) {
        status.textContent = `✅ AI สร้างรายได้ให้คุณ +${data.amount} บาท`;
        status.classList.remove('hidden');

        // ✅ หากสุ่มได้ให้เปิดลิงก์ Shopee
        if (data.show_affiliate && data.shopee_url) {
          setTimeout(() => {
            window.open(data.shopee_url, '_blank');
          }, 1000);
        }

      } else {
        // ⚠️ กรณีคลิกเร็วเกินไป
        status.textContent = `⚠️ ${data.message}`;
        status.classList.remove('hidden');
      }

      // ซ่อนข้อความหลัง 3 วินาที
      setTimeout(() => {
        status.classList.add('hidden');
      }, 3000);
    });
}
</script>





 

    </div>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
      <div class="bg-gradient-to-br from-green-400 to-lime-500 p-6 rounded-xl text-center shadow-xl">
        <p class="text-sm uppercase">รายได้วันนี้</p>
        <p class="text-3xl font-bold">฿<span id="todayIncome">0</span></p>
      </div>
      <div class="bg-gradient-to-br from-yellow-400 to-orange-500 p-6 rounded-xl text-center shadow-xl">
        <p class="text-sm uppercase">รายได้สะสม</p>
        <p class="text-3xl font-bold">฿<?php echo $totalIncome; ?></p>
      </div>
      <div class="bg-gradient-to-br from-blue-400 to-indigo-600 p-6 rounded-xl text-center shadow-xl">
        <p class="text-sm uppercase">โบนัสพิเศษ</p>
        <p class="text-3xl font-bold">+<span id="bonusAmount">0</span> ฿</p>
      </div>
    </div>

  <div class="relative w-full h-[350px] overflow-hidden rounded-xl bg-black my-6">
  <svg viewBox="0 0 800 300" class="absolute w-full h-full">
    <defs>
      <radialGradient id="glowGreen" r="50%">
        <stop offset="0%" stop-color="#00ff99" stop-opacity="1"/>
        <stop offset="100%" stop-color="#00ff99" stop-opacity="0"/>
      </radialGradient>
      <radialGradient id="glowBlue" r="50%">
        <stop offset="0%" stop-color="#33ccff" stop-opacity="1"/>
        <stop offset="100%" stop-color="#33ccff" stop-opacity="0"/>
      </radialGradient>
      <radialGradient id="glowPink" r="50%">
        <stop offset="0%" stop-color="#ff33cc" stop-opacity="1"/>
        <stop offset="100%" stop-color="#ff33cc" stop-opacity="0"/>
      </radialGradient>
    </defs>

    <!-- เส้นเครือข่าย -->
    <g stroke-opacity="0.15">
      <line x1="100" y1="200" x2="250" y2="100" stroke="#00ff99" />
      <line x1="250" y1="100" x2="600" y2="120" stroke="#33ccff" />
      <line x1="600" y1="120" x2="400" y2="250" stroke="#ff33cc" />
      <line x1="400" y1="250" x2="100" y2="200" stroke="#ffaa00" />
      <line x1="250" y1="100" x2="400" y2="250" stroke="#ffffff" stroke-opacity="0.08" />
      <line x1="100" y1="80" x2="600" y2="120" stroke="#ffcc00" stroke-opacity="0.08" />
      <line x1="600" y1="200" x2="200" y2="50" stroke="#cc66ff" stroke-opacity="0.08" />
    </g>

    <!-- จุดข้อมูล AI วิ่งหลายจุด -->
    <circle r="7" fill="url(#glowGreen)">
      <animateMotion dur="8s" repeatCount="indefinite" rotate="auto">
        <mpath href="#p1" />
      </animateMotion>
    </circle>
    <circle r="7" fill="url(#glowBlue)">
      <animateMotion dur="10s" repeatCount="indefinite" rotate="auto" begin="2s">
        <mpath href="#p2" />
      </animateMotion>
    </circle>
    <circle r="7" fill="url(#glowPink)">
      <animateMotion dur="12s" repeatCount="indefinite" rotate="auto" begin="4s">
        <mpath href="#p3" />
      </animateMotion>
    </circle>

    <!-- เส้นทางวิ่ง -->
    <path id="p1" d="M100,200 C200,80 400,80 600,120 S300,270 400,250" fill="none"/>
    <path id="p2" d="M100,80 C250,0 550,0 600,120 S450,280 400,250" fill="none"/>
    <path id="p3" d="M600,200 C500,160 300,140 100,200" fill="none"/>
  </svg>

  <!-- ข้อความแสดงผล -->
  <div class="absolute top-4 w-full text-center text-lime-400 font-medium animate-pulse">
    🧠 ระบบ AI เชื่อมต่อเครือข่ายเพื่อสร้างรายได้...
  </div>
</div>


    
<!-- REFERRAL BONUS SECTION -->
<div class="bg-black/30 p-4 rounded-xl text-sm text-white text-center my-6">
  🔗 ลิงก์แนะนำของคุณ:<br>
  <span class="text-green-400 select-all">https://ruaysabai.com/register.php?ref=<?php echo $_SESSION['username']; ?></span><br>
  👥 แนะนำแล้ว <?php echo $refTotalCount; ?> คน ได้รับแล้ว ฿<?php echo $refTotal; ?> บาท<br>
  🧾 รายได้จากเติมเงิน: ฿<?php echo $refFromTopup; ?> |
  รายได้จากระบบ AI: ฿<?php echo $refFromIncome; ?>
</div>


<script>
let cooldown = <?php echo $cooldownLeft; ?>;
let lastEarned = <?php echo $lastEarn; ?>;
let timer;

function claimIncome() {
  if (cooldown > 0) return;
  fetch('income.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('lastEarned').innerText = `✅ คุณได้รับรายได้ +${data.amount} บาท`;
        document.getElementById('lastEarned').classList.remove('hidden');
        cooldown = <?php echo $settings['minutes'] * 60; ?>;
        document.getElementById('incomeStatus').classList.remove('hidden');
        document.getElementById('claimBtn').disabled = true;
        document.getElementById('claimBtn').classList.add('opacity-50');
        startCountdown();
      }
    });
}

function startCountdown() {
  timer = setInterval(() => {
    cooldown--;
    document.getElementById('cooldown').innerText = cooldown;
    if (cooldown <= 0) {
      clearInterval(timer);
	  
      document.getElementById('incomeStatus').classList.add('hidden');
      document.getElementById('claimBtn').disabled = false;
      document.getElementById('claimBtn').classList.remove('opacity-50');
    }
  }, 1000);
}

window.onload = () => {
  if (cooldown > 0) {
    document.getElementById('claimBtn').disabled = true;
    document.getElementById('claimBtn').classList.add('opacity-50');
    document.getElementById('incomeStatus').classList.remove('hidden');
    if (lastEarned > 0) {
      document.getElementById('lastEarned').innerText = `✅ คุณได้รับรายได้ +${lastEarned} บาท`;
      document.getElementById('lastEarned').classList.remove('hidden');
    }
    startCountdown();
  }
}

// สุ่มออนไลน์/ข้อความ/กราฟ
let todayIncome = 0;
let bonusAmount = 0;
let online = 25;
const onlineEl = document.getElementById('onlineCount');
const marquee = document.getElementById('marqueeBox');
const incomeEl = document.getElementById('todayIncome');
const bonusEl = document.getElementById('bonusAmount');

const ctx = document.getElementById('incomeChart').getContext('2d');
const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: Array.from({ length: 20 }, () => ''),
    datasets: [{
      label: 'รายได้สุ่ม',
      data: Array.from({ length: 20 }, () => Math.floor(Math.random() * 100)),
      borderColor: 'lime',
      backgroundColor: 'rgba(0,255,0,0.1)',
      tension: 0.4
    }]
  },
  options: { animation: false, scales: { y: { beginAtZero: true } } }
});

const messages = [
  "แอนนา เติมเงิน 300 บาท", "โบนัส AI อัปเดตแล้ว", "ถอนเงิน 150 บาท สำเร็จ",
  "อัปเกรด VIP สำเร็จ", "รับรายได้จากระบบ AI", "สุ่มกล่องโบนัส 80 บาท",
  "ผู้ใช้ใหม่เข้าระบบ 3 คน", "เชื่อมต่อจากจังหวัดกรุงเทพ", "AI แจกโบนัสใหม่"
];

function updateMarquee() {
  const random = [...messages].sort(() => 0.5 - Math.random());
  marquee.innerText = random.slice(0, 4).join(' • ');
}
function updateStats() {
  todayIncome += Math.floor(Math.random() * 10);
  bonusAmount = Math.floor(Math.random() * 5);
  incomeEl.innerText = todayIncome;
  bonusEl.innerText = bonusAmount;
  chart.data.datasets[0].data.push(Math.floor(Math.random() * 100));
  if (chart.data.datasets[0].data.length > 20) chart.data.datasets[0].data.shift();
  chart.update();
}

setInterval(() => { online += Math.floor(Math.random() * 5 - 2); if (online < 10) online = 10; onlineEl.innerText = online; }, 5000);
setInterval(updateMarquee, 10000);
setInterval(updateStats, 1500);
updateMarquee();
</script>
</body>
</html>
