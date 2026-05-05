<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$activePage = 'report';
$today = strtoupper(date('l, F j'));
$reportError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_id <= 0) {
        $reportError = 'Please sign in again before submitting a report.';
    } elseif (!isset($conn) || $conn->connect_error) {
        $reportError = 'Database connection failed. Please try again.';
    } else {
    $required = ['incident_type','description','date','time'];
    $ok = true;
    foreach ($required as $f) { if (empty($_POST[$f])) $ok = false; }
    if ($ok) {
        $stmt = $conn->prepare("INSERT INTO incidents (user_id, incident_type, description, location, date_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            $reportError = 'Could not prepare the report save. Please try again.';
        } else {
            $dt = $_POST['date'].' '.$_POST['time'];
            $loc = $_POST['location'] ?? 'Unknown';
            $stmt->bind_param("issss", $user_id, $_POST['incident_type'], $_POST['description'], $loc, $dt);
            if ($stmt->execute()) { header('Location: map.php'); exit(); }
            $reportError = 'Could not save the report. Please try again.';
            $stmt->close();
        }
    } else {
        $reportError = 'Please fill all required fields.';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident — SheShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div>
        <div class="topbar-date"><?= $today ?> — REPORTS</div>
        <h1 class="topbar-title">Report <span class="gradient-text">Incident</span></h1>
      </div>
      <div class="topbar-right">
        <a href="../../pro/report.html" class="btn-report" style="background:linear-gradient(135deg,#ef4444,#991b1b)">🔴 SOS Emergency</a>
      </div>
    </div>
    <div class="dash-inner">
      <div style="display:grid;grid-template-columns:1fr 360px;gap:20px">
        <!-- Report Form -->
        <div class="glass-card">
          <h3 style="font-size:18px;font-weight:700;margin-bottom:6px">File a Safety Report</h3>
          <p style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:24px">All reports are confidential and encrypted</p>
          <?php if ($reportError): ?>
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5;border-radius:12px;padding:12px 14px;font-size:12px;margin-bottom:16px"><?= htmlspecialchars($reportError) ?></div>
          <?php endif; ?>
          <form method="POST" style="display:flex;flex-direction:column;gap:16px">
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Email</label>
              <input type="email" name="email" placeholder="your@email.com" class="premium-input">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div>
                <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Date</label>
                <input type="date" name="date" class="premium-input" required>
              </div>
              <div>
                <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Time</label>
                <input type="time" name="time" class="premium-input" required>
              </div>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Incident Type</label>
              <select name="incident_type" class="premium-input" required>
                <option value="">Select type...</option>
                <option value="harassment">Harassment</option>
                <option value="stalking">Stalking</option>
                <option value="theft">Theft</option>
                <option value="assault">Assault</option>
                <option value="cybercrime">Cybercrime</option>
                <option value="domestic">Domestic Violence</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Location</label>
              <select name="location" class="premium-input" required>
                <option value="">Select location...</option>
                <option value="Block 32">Block 32</option>
                <option value="Block 34">Block 34</option>
                <option value="Girls Hostel">Girls Hostel</option>
                <option value="Boys Hostel">Boys Hostel</option>
                <option value="Uni Mall">Uni Mall</option>
                <option value="Main Gate">Main Gate</option>
                <option value="Library">Library</option>
                <option value="Sports Complex">Sports Complex</option>
                <option value="Parking Zone">Parking Zone</option>
                <option value="Other">Other (specify in description)</option>
              </select>
            </div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Description</label>
              <textarea name="description" placeholder="Describe the incident in detail..." class="premium-input" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn-primary" style="padding:14px;font-size:14px;border-radius:12px;margin-top:8px">
              <i class="fa-solid fa-paper-plane" style="margin-right:8px"></i> Submit Report
            </button>
          </form>
        </div>

        <!-- Emergency Sidebar -->
        <div style="display:flex;flex-direction:column;gap:14px">
          <div class="sos-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
              <h3 style="font-size:14px;font-weight:600;color:#f87171"><i class="fa-solid fa-phone-volume" style="margin-right:6px"></i> Emergency</h3>
              <span class="tag-pill red" style="animation:pulse 2s infinite">24/7</span>
            </div>
            <button class="sos-btn" onclick="window.location.href='tel:112'" style="margin-bottom:12px">🔴 ACTIVATE SOS</button>
            <p style="font-size:10px;color:rgba(255,255,255,0.3);text-align:center">Alerts all emergency contacts instantly</p>
          </div>

          <?php
          $contacts = [
            ['LPU Security','1800-102-4431','#e91e8c'],
            ['Campus Emergency','+91-1824-517000','#7c3aed'],
            ['Women Helpline','1091','#ef4444'],
            ['Police','100','#3b82f6']
          ];
          foreach ($contacts as $c): ?>
          <div class="glass-card" style="padding:14px">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-size:12px;font-weight:600"><?= $c[0] ?></div>
                <div style="font-size:14px;font-weight:700;color:<?= $c[2] ?>;margin-top:2px"><?= $c[1] ?></div>
              </div>
              <div style="display:flex;gap:6px">
                <button class="btn-secondary" style="padding:6px 10px" onclick="navigator.clipboard.writeText('<?= $c[1] ?>')"><i class="fa-regular fa-copy" style="font-size:11px"></i></button>
                <a href="tel:<?= $c[1] ?>" class="btn-primary" style="padding:6px 10px;text-decoration:none"><i class="fa-solid fa-phone" style="font-size:11px"></i></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <div class="glass-card" style="padding:14px">
            <h4 style="font-size:12px;font-weight:600;color:#f472b6;margin-bottom:8px"><i class="fa-solid fa-shield-heart" style="margin-right:6px"></i>Safety Tips</h4>
            <ul style="font-size:11px;color:rgba(255,255,255,0.4);list-style:none;display:flex;flex-direction:column;gap:6px">
              <li>• Stay in well-lit areas at night</li>
              <li>• Share your live location with trusted contacts</li>
              <li>• Save emergency numbers on speed dial</li>
              <li>• Trust your instincts — leave if unsafe</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
</body>
</html>
