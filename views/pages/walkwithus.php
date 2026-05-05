<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$user_name = isset($_SESSION['first_name']) ? trim($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')) : 'User';
$activePage = 'walkwithus';
$today = strtoupper(date('l, F j'));

$locations = ['Block 32','Block 34','Girls Hostel','Boys Hostel','Uni Mall','Main Gate','Library','Sports Complex','Parking Zone','Other'];
$areas = ['Block 32-34','Library Area','Uni Mall','Girls Hostel','Sports Complex','Main Gate','Parking Zone','Academic Block'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk With Us — SheShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="stylesheet" href="../../public/css/dashboard.css?v=4">
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div>
        <div class="topbar-date"><?= $today ?> — WALK WITH US</div>
        <h1 class="topbar-title">Walk <span class="gradient-text">With Us</span></h1>
      </div>
      <div class="topbar-right"><a href="report.php" class="btn-report">+ New Report</a></div>
    </div>
    <div class="dash-inner">
      <!-- Feature chips -->
      <div class="grid-3 mb-24">
        <div class="chip-card">
          <div style="width:36px;height:36px;border-radius:10px;background:rgba(233,30,140,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fa-solid fa-shield-halved" style="color:#e91e8c"></i></div>
          <div><div style="font-size:13px;font-weight:600">Verified Escorts</div><div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px">All walkers are background verified</div></div>
        </div>
        <div class="chip-card">
          <div style="width:36px;height:36px;border-radius:10px;background:rgba(124,58,237,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fa-solid fa-location-crosshairs" style="color:#7c3aed"></i></div>
          <div><div style="font-size:13px;font-weight:600">Real-time Tracking</div><div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px">Live GPS location sharing enabled</div></div>
        </div>
        <div class="chip-card">
          <div style="width:36px;height:36px;border-radius:10px;background:rgba(34,197,94,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fa-solid fa-clock" style="color:#22c55e"></i></div>
          <div><div style="font-size:13px;font-weight:600">24/7 Available</div><div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px">Request a walk anytime day or night</div></div>
        </div>
      </div>

      <!-- Two-column form -->
      <div class="grid-1-1">
        <!-- Request a Walk -->
        <div class="glass-card">
          <h3 style="font-size:18px;font-weight:700;margin-bottom:4px">Request a Walk</h3>
          <p style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:20px">A verified walker will accompany you safely</p>
          <form id="walkRequestForm" style="display:flex;flex-direction:column;gap:14px">
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Pickup Location</label>
              <select id="pickupLocation" class="premium-input" required>
                <option value="">Select pickup location...</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc ?>"><?= $loc ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Destination</label>
              <select id="destination" class="premium-input" required>
                <option value="">Select destination...</option>
                <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc ?>"><?= $loc ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Preferred Time</label>
              <input type="datetime-local" id="preferredTime" class="premium-input">
            </div>
            <button type="button" class="btn-primary" id="btnRequestWalk" style="padding:14px;font-size:14px;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px">
              <i class="fa-solid fa-person-walking"></i> Request Walk
            </button>
          </form>
          <!-- Live walkers -->
          <div style="margin-top:16px;display:flex;align-items:center;gap:10px">
            <div style="display:flex">
              <?php for($i=0;$i<5;$i++): $colors=['#e91e8c','#7c3aed','#3b82f6','#22c55e','#f59e0b']; ?>
              <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,<?= $colors[$i] ?>,<?= $colors[($i+1)%5] ?>);border:2px solid #0d0d14;margin-left:<?= $i>0?'-8px':'0' ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600"><?= chr(65+$i) ?></div>
              <?php endfor; ?>
            </div>
            <span style="font-size:11px;color:rgba(255,255,255,0.4)"><span style="color:#22c55e;font-weight:600">7 walkers</span> active near you</span>
          </div>
        </div>

        <!-- Volunteer -->
        <div class="glass-card">
          <h3 style="font-size:18px;font-weight:700;margin-bottom:4px">Volunteer as Walker</h3>
          <p style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:20px">Help others feel safe on campus</p>
          <form id="walkerRegForm" style="display:flex;flex-direction:column;gap:14px">
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Available From</label>
              <input type="time" id="availFrom" class="premium-input" required>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Available Until</label>
              <input type="time" id="availUntil" class="premium-input" required>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Preferred Areas</label>
              <div id="selectedAreas" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px"></div>
              <div style="display:flex;flex-wrap:wrap;gap:6px">
                <?php foreach ($areas as $area): ?>
                <button type="button" class="area-chip" onclick="toggleArea(this,'<?= $area ?>')" style="padding:6px 12px;border-radius:8px;font-size:11px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.5);cursor:pointer;transition:all 0.2s"><?= $area ?></button>
                <?php endforeach; ?>
              </div>
            </div>
            <button type="button" class="btn-primary" id="btnRegisterWalker" style="padding:14px;font-size:14px;border-radius:12px;display:flex;align-items:center;justify-content:center;gap:8px">
              <i class="fa-solid fa-check"></i> Register as Walker
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
const selectedAreas = new Set();
const userId = <?= $user_id ?>;
const userEmail = '<?= addslashes($user_email) ?>';
const userName = '<?= addslashes($user_name) ?>';

function toggleArea(btn, area) {
  if (selectedAreas.has(area)) {
    selectedAreas.delete(area);
    btn.style.background = 'rgba(255,255,255,0.04)';
    btn.style.borderColor = 'rgba(255,255,255,0.08)';
    btn.style.color = 'rgba(255,255,255,0.5)';
  } else {
    selectedAreas.add(area);
    btn.style.background = 'rgba(233,30,140,0.15)';
    btn.style.borderColor = 'rgba(233,30,140,0.3)';
    btn.style.color = '#f472b6';
  }
}

// Request Walk
document.getElementById('btnRequestWalk').addEventListener('click', async function() {
  const pickup = document.getElementById('pickupLocation').value;
  const dest = document.getElementById('destination').value;
  const time = document.getElementById('preferredTime').value;

  if (!pickup) { showToast('Please select a pickup location.', 'error'); return; }
  if (!dest) { showToast('Please select a destination.', 'error'); return; }
  if (pickup === dest) { showToast('Pickup and destination cannot be the same.', 'error'); return; }

  this.disabled = true;
  this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

  try {
    const res = await fetch('../../api/walks/send_walk_email.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'walk_request',
        userId: userId,
        userName: userName,
        userEmail: userEmail,
        pickupLocation: pickup,
        destination: dest,
        requestTime: time || new Date().toLocaleString()
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('Walk request sent! Confirmation email delivered.', 'success');
      document.getElementById('walkRequestForm').reset();
    } else {
      showToast(data.message || 'Failed to send request.', 'error');
    }
  } catch (err) {
    showToast('Network error. Please try again.', 'error');
  }

  this.disabled = false;
  this.innerHTML = '<i class="fa-solid fa-person-walking"></i> Request Walk';
});

// Register as Walker
document.getElementById('btnRegisterWalker').addEventListener('click', async function() {
  const from = document.getElementById('availFrom').value;
  const until = document.getElementById('availUntil').value;

  if (!from || !until) { showToast('Please set your availability times.', 'error'); return; }
  if (selectedAreas.size === 0) { showToast('Please select at least one preferred area.', 'error'); return; }

  this.disabled = true;
  this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering...';

  try {
    const res = await fetch('../../api/walks/send_walk_email.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'walker_register',
        userId: userId,
        userName: userName,
        userEmail: userEmail,
        availableFrom: from,
        availableUntil: until,
        preferredAreas: Array.from(selectedAreas)
      })
    });
    const data = await res.json();
    if (data.success) {
      showToast('Registered as walker! Confirmation email sent.', 'success');
      document.getElementById('walkerRegForm').reset();
      selectedAreas.clear();
      document.querySelectorAll('.area-chip').forEach(b => {
        b.style.background = 'rgba(255,255,255,0.04)';
        b.style.borderColor = 'rgba(255,255,255,0.08)';
        b.style.color = 'rgba(255,255,255,0.5)';
      });
    } else {
      showToast(data.message || 'Failed to register.', 'error');
    }
  } catch (err) {
    showToast('Network error. Please try again.', 'error');
  }

  this.disabled = false;
  this.innerHTML = '<i class="fa-solid fa-check"></i> Register as Walker';
});
</script>
<?php include __DIR__.'/toast.php'; ?>
</body>
</html>
