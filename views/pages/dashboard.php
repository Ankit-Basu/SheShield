<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$firstName = isset($_SESSION['first_name']) ? trim($_SESSION['first_name']) : '';
$activePage = 'dashboard'; $today = strtoupper(date('l, F j'));

// Real-time stats from DB
$total_reports = 0; $resolved = 0; $pending = 0; $active_walkers = 0; $safe_spaces = 0;
$incidents = [];

if (isset($conn) && !$conn->connect_error) {
    if ($user_id > 0 && $firstName === '') {
        $stmt = $conn->prepare("SELECT first_name, last_name, full_name, email FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $firstName = trim($row['first_name'] ?? '');
                if ($firstName === '' && !empty($row['full_name'])) {
                    $firstName = trim(explode(' ', $row['full_name'])[0]);
                }
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $row['last_name'] ?? '';
                $_SESSION['email'] = $row['email'] ?? '';
            }
            $stmt->close();
        }
    }

    // User-specific counts
    if ($user_id > 0) {
        $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE user_id = $user_id");
        if ($r) $total_reports = (int)mysqli_fetch_assoc($r)['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE user_id = $user_id AND status='resolved'");
        if ($r) $resolved = (int)mysqli_fetch_assoc($r)['c'];
        $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE user_id = $user_id AND status='pending'");
        if ($r) $pending = (int)mysqli_fetch_assoc($r)['c'];
        $r = $conn->query("SELECT * FROM incidents WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
        if ($r) while ($row = $r->fetch_assoc()) $incidents[] = $row;
    }
    // Global counts
    $r = $conn->query("SELECT COUNT(*) as c FROM escorts WHERE status='active'");
    if ($r) $active_walkers = (int)mysqli_fetch_assoc($r)['c'];
    $r = $conn->query("SELECT COUNT(*) as c FROM safe_spaces");
    if ($r) $safe_spaces = (int)mysqli_fetch_assoc($r)['c'];
    $r2 = $conn->query("SELECT COUNT(*) as c FROM safe_zone");
    if ($r2) $safe_spaces += (int)mysqli_fetch_assoc($r2)['c'];

    // Incident breakdown for progress bars
    $type_counts = [];
    if ($user_id > 0) {
        $r = $conn->query("SELECT incident_type, COUNT(*) as c FROM incidents WHERE user_id = $user_id GROUP BY incident_type ORDER BY c DESC");
        if ($r) while ($row = $r->fetch_assoc()) $type_counts[$row['incident_type']] = (int)$row['c'];
    }
}
$firstName = htmlspecialchars($firstName !== '' ? $firstName : 'User');
$type_max = !empty($type_counts) ? max($type_counts) : 1;
$colors_map = ['harassment'=>'#e91e8c','stalking'=>'#7c3aed','cybercrime'=>'#3b82f6','theft'=>'#f59e0b','assault'=>'#ef4444','domestic'=>'#f87171','other'=>'#6b7280'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard — SheShield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
  <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__.'/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div>
        <div class="topbar-date"><?= $today ?> — Welcome back, <?= $firstName ?></div>
        <h1 class="topbar-title">Dashboard <span class="gradient-text">Overview</span></h1>
      </div>
      <div class="topbar-right">
        <div class="search-bar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" placeholder="Search..."></div>
        <button class="btn-notification"><i class="fa-solid fa-bell"></i><?php if($pending>0):?><span class="badge"><?=$pending?></span><?php endif;?></button>
        <a href="report.php" class="btn-report">+ New Report</a>
      </div>
    </div>
    <div class="dash-inner">
      <!-- Stats Row (real-time) -->
      <div class="grid-4 mb-24">
        <div class="stat-card rose"><div class="stat-icon rose"><i class="fa-solid fa-file-alt"></i></div><div class="stat-number"><?=$total_reports?></div><div class="stat-label">Total Reports</div><div class="stat-trend up"><i class="fa-solid fa-database" style="font-size:10px"></i> Real-time</div></div>
        <div class="stat-card violet"><div class="stat-icon violet"><i class="fa-solid fa-check-circle"></i></div><div class="stat-number"><?=$resolved?></div><div class="stat-label">Resolved</div><div class="stat-trend up"><i class="fa-solid fa-database" style="font-size:10px"></i> Live data</div></div>
        <div class="stat-card amber"><div class="stat-icon amber"><i class="fa-solid fa-person-walking"></i></div><div class="stat-number"><?=$active_walkers?></div><div class="stat-label">Active Walkers</div><div class="stat-trend up"><i class="fa-solid fa-circle" style="font-size:6px"></i> From escorts table</div></div>
        <div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-shield-heart"></i></div><div class="stat-number"><?=$safe_spaces?></div><div class="stat-label">Safe Spaces</div><div class="stat-trend up"><i class="fa-solid fa-database" style="font-size:10px"></i> Live data</div></div>
      </div>

      <!-- Feature Cards -->
      <div class="grid-3 mb-24">
        <a href="analytics.php" class="feature-card"><div class="card-icon" style="background:rgba(233,30,140,0.12);color:#e91e8c"><i class="fa-solid fa-chart-line"></i></div><div class="card-title">Analytics Dashboard</div><div class="card-desc">View detailed safety analytics, incident trends, and campus safety scores.</div><div class="card-link rose">Explore Analytics <i class="fa-solid fa-arrow-right" style="font-size:11px"></i></div></a>
        <a href="report.php" class="feature-card"><div class="card-icon" style="background:rgba(124,58,237,0.12);color:#7c3aed"><i class="fa-solid fa-file-alt"></i></div><div class="card-title">Reports</div><div class="card-desc">File new complaints and track case documentation in real-time.</div><div class="card-link violet">View Reports <i class="fa-solid fa-arrow-right" style="font-size:11px"></i></div></a>
        <a href="map.php" class="feature-card"><div class="card-icon" style="background:rgba(52,211,153,0.12);color:#34d399"><i class="fa-solid fa-map-location-dot"></i></div><div class="card-title">Safety Map</div><div class="card-desc">Interactive map showing incident density and real-time campus safety zones.</div><div class="card-link green">Open Map <i class="fa-solid fa-arrow-right" style="font-size:11px"></i></div></a>
      </div>

      <!-- Bottom Row -->
      <div class="grid-2-1 mb-24">
        <div class="glass-card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <h3 style="font-size:16px;font-weight:600">Incident Breakdown</h3>
            <span class="tag-pill violet">Real-time</span>
          </div>
          <?php if (empty($type_counts)): ?>
            <p style="font-size:13px;color:rgba(255,255,255,0.4);text-align:center;padding:20px 0">No incidents reported yet. Data will appear here in real-time.</p>
          <?php else: foreach ($type_counts as $type => $count):
            $pct = round(($count / $type_max) * 100);
            $color = $colors_map[strtolower($type)] ?? '#6b7280';
          ?>
          <div class="progress-row">
            <span class="progress-label"><?= ucfirst($type) ?></span>
            <div class="progress-track"><div class="progress-fill" style="width:<?=$pct?>%;background:<?=$color?>"></div></div>
            <span class="progress-count"><?=$count?></span>
          </div>
          <?php endforeach; endif; ?>
        </div>
        <div class="sos-card">
          <h3 style="font-size:16px;font-weight:600;margin-bottom:4px">Quick SOS</h3>
          <p style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:16px">One-tap emergency alert</p>
          <button class="sos-btn" onclick="window.location.href='../../pro/report.html'">🔴 Activate SOS</button>
          <p style="font-size:10px;color:rgba(255,255,255,0.3);margin-top:12px;text-align:center">Emergency contacts will be notified instantly</p>
        </div>
      </div>

      <!-- Latest Incidents (real-time) -->
      <div class="glass-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
          <h3 style="font-size:16px;font-weight:600">Latest Incident Reports</h3>
          <span class="tag-pill green">Live</span>
        </div>
        <?php if (empty($incidents)): ?>
          <p style="font-size:13px;color:rgba(255,255,255,0.4);text-align:center;padding:20px 0">No incidents reported yet. <a href="report.php" style="color:#f472b6">File a report →</a></p>
        <?php else: foreach ($incidents as $inc):
          $type=htmlspecialchars($inc['incident_type']??'Unknown');
          $desc=htmlspecialchars(substr($inc['description']??'',0,60)).'...';
          $status=$inc['status']??'pending';
          $sc=$status==='resolved'?'green':($status==='pending'?'amber':'red');
          $date=date('M j, g:i A',strtotime($inc['created_at']??'now'));
        ?>
        <div class="incident-row">
          <span class="tag-pill rose"><?=ucfirst($type)?></span>
          <span style="flex:1;font-size:13px;color:rgba(255,255,255,0.6)"><?=$desc?></span>
          <span class="tag-pill <?=$sc?>"><?=ucfirst($status)?></span>
          <span style="font-size:11px;color:rgba(255,255,255,0.3)"><?=$date?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
</body>
</html>
