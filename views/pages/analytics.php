<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__.'/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$activePage = 'analytics'; $today = strtoupper(date('l, F j'));

$total=0; $resolved=0; $pending=0; $rate=0; $recent=0;
$status_labels=[]; $status_values=[];
$type_labels=[]; $type_values=[];
$monthly_labels=[]; $monthly_values=[];
$weekly_labels=[]; $weekly_values=[];

if (isset($conn) && !$conn->connect_error) {
    // Total incidents (all users for global view, or per-user)
    $uid_filter = $user_id > 0 ? "WHERE user_id = $user_id" : "";
    $uid_and = $user_id > 0 ? "AND user_id = $user_id" : "";

    $r = $conn->query("SELECT COUNT(*) as c FROM incidents $uid_filter");
    if ($r) $total = (int)mysqli_fetch_assoc($r)['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE status='resolved' $uid_and");
    if ($r) $resolved = (int)mysqli_fetch_assoc($r)['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE status='pending' $uid_and");
    if ($r) $pending = (int)mysqli_fetch_assoc($r)['c'];

    $rate = $total > 0 ? round(($resolved/$total)*100) : 0;

    $r = $conn->query("SELECT COUNT(*) as c FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $uid_and");
    if ($r) $recent = (int)mysqli_fetch_assoc($r)['c'];

    // Status breakdown (for doughnut)
    $r = $conn->query("SELECT status, COUNT(*) as c FROM incidents $uid_filter GROUP BY status ORDER BY c DESC");
    if ($r) while ($row = $r->fetch_assoc()) { $status_labels[] = ucfirst($row['status']); $status_values[] = (int)$row['c']; }

    // Type breakdown (for horizontal bar)
    $r = $conn->query("SELECT incident_type, COUNT(*) as c FROM incidents $uid_filter GROUP BY incident_type ORDER BY c DESC LIMIT 6");
    if ($r) while ($row = $r->fetch_assoc()) { $type_labels[] = ucfirst($row['incident_type']); $type_values[] = (int)$row['c']; }

    // Monthly trend (for bar chart)
    $r = $conn->query("SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as c FROM incidents $uid_filter GROUP BY MONTH(created_at) ORDER BY MONTH(created_at) ASC");
    if ($r) while ($row = $r->fetch_assoc()) { $monthly_labels[] = $row['month']; $monthly_values[] = (int)$row['c']; }

    // Weekly trend (for line chart)
    $r = $conn->query("SELECT DAYNAME(created_at) as day, COUNT(*) as c FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) $uid_and GROUP BY DAYOFWEEK(created_at) ORDER BY DAYOFWEEK(created_at)");
    if ($r) while ($row = $r->fetch_assoc()) { $weekly_labels[] = substr($row['day'],0,3); $weekly_values[] = (int)$row['c']; }
}

$has_data = $total > 0;
$status_colors = "['rgba(34,197,94,0.8)','rgba(245,158,11,0.8)','rgba(239,68,68,0.8)','rgba(124,58,237,0.8)']";
$type_colors = "['rgba(233,30,140,0.7)','rgba(124,58,237,0.7)','rgba(59,130,246,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(34,197,94,0.7)']";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Analytics — SheShield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__.'/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div><div class="topbar-date"><?=$today?> — ANALYTICS</div><h1 class="topbar-title">Safety <span class="gradient-text">Analytics</span></h1></div>
      <div class="topbar-right"><span class="tag-pill <?=$has_data?'green':'amber'?>"><?=$has_data?'Live Data':'No Data Yet'?></span><a href="report.php" class="btn-report">+ New Report</a></div>
    </div>
    <div class="dash-inner">
      <!-- Stats (real-time) -->
      <div class="grid-4 mb-24">
        <div class="stat-card rose"><div class="stat-icon rose"><i class="fa-solid fa-file-alt"></i></div><div class="stat-number"><?=$total?></div><div class="stat-label">Total Incidents</div><div class="stat-trend up"><i class="fa-solid fa-database" style="font-size:10px"></i> Real-time</div></div>
        <div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div><div class="stat-number"><?=$rate?>%</div><div class="stat-label">Resolution Rate</div><div class="stat-trend up"><?=$resolved?> of <?=$total?> resolved</div></div>
        <div class="stat-card amber"><div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div><div class="stat-number"><?=$recent?></div><div class="stat-label">This Week</div><div class="stat-trend up">Last 7 days</div></div>
        <div class="stat-card violet"><div class="stat-icon violet"><i class="fa-solid fa-bolt"></i></div><div class="stat-number"><?=$pending?></div><div class="stat-label">Pending Cases</div><div class="stat-trend <?=$pending>0?'down':'up'?>"><?=$pending>0?'Needs attention':'All clear'?></div></div>
      </div>

      <?php if ($has_data): ?>
      <!-- Charts (real DB data) -->
      <div class="grid-4 mb-24">
        <div class="chart-card"><div class="chart-title">Resolution Status</div><div class="chart-subtitle">From incidents table</div><canvas id="resChart"></canvas></div>
        <div class="chart-card"><div class="chart-title">Weekly Activity</div><div class="chart-subtitle">Incidents this week</div><div style="height:220px"><canvas id="weekChart"></canvas></div></div>
        <div class="chart-card"><div class="chart-title">Monthly Trend</div><div class="chart-subtitle">Incidents per month</div><div style="height:220px"><canvas id="monthChart"></canvas></div></div>
        <div class="chart-card"><div class="chart-title">By Category</div><div class="chart-subtitle">Incident type breakdown</div><div style="height:220px"><canvas id="typeChart"></canvas></div></div>
      </div>
      <?php else: ?>
      <!-- No data state -->
      <div class="glass-card" style="text-align:center;padding:60px 24px">
        <div style="font-size:48px;margin-bottom:16px">📊</div>
        <h3 style="font-size:18px;font-weight:700;margin-bottom:8px">No Data Yet</h3>
        <p style="font-size:13px;color:rgba(255,255,255,0.4);max-width:400px;margin:0 auto 20px">Analytics charts will appear here once incidents are reported. All data is pulled in real-time from the database.</p>
        <a href="report.php" class="btn-primary" style="text-decoration:none;padding:12px 24px;border-radius:12px;display:inline-block">File First Report →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
<?php if ($has_data): ?>
<script>
Chart.defaults.color='rgba(255,255,255,0.5)';
Chart.defaults.borderColor='rgba(255,255,255,0.06)';
Chart.defaults.font.family="'Inter',sans-serif";
const tp={backgroundColor:'rgba(13,13,20,0.9)',borderColor:'rgba(255,255,255,0.1)',borderWidth:1,titleColor:'#fff',bodyColor:'rgba(255,255,255,0.6)'};

// Doughnut — status breakdown
new Chart(document.getElementById('resChart'),{type:'doughnut',data:{labels:<?=json_encode($status_labels)?>,datasets:[{data:<?=json_encode($status_values)?>,backgroundColor:<?=$status_colors?>,borderColor:'rgba(255,255,255,0.05)',borderWidth:2,hoverOffset:8}]},options:{responsive:true,cutout:'72%',plugins:{legend:{position:'bottom',labels:{color:'rgba(255,255,255,0.6)',padding:16,usePointStyle:true}},tooltip:tp}}});

// Line — weekly
<?php if(!empty($weekly_labels)):?>
const wCtx=document.getElementById('weekChart').getContext('2d');
const wGrad=wCtx.createLinearGradient(0,0,0,220);wGrad.addColorStop(0,'rgba(233,30,140,0.4)');wGrad.addColorStop(1,'rgba(233,30,140,0)');
new Chart(wCtx,{type:'line',data:{labels:<?=json_encode($weekly_labels)?>,datasets:[{data:<?=json_encode($weekly_values)?>,borderColor:'#e91e8c',backgroundColor:wGrad,borderWidth:2.5,pointBackgroundColor:'#e91e8c',pointRadius:5,tension:.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,scales:{x:{grid:{color:'rgba(255,255,255,0.04)'}},y:{grid:{color:'rgba(255,255,255,0.04)'},beginAtZero:true}},plugins:{legend:{display:false},tooltip:tp}}});
<?php endif;?>

// Bar — monthly
<?php if(!empty($monthly_labels)):?>
new Chart(document.getElementById('monthChart'),{type:'bar',data:{labels:<?=json_encode($monthly_labels)?>,datasets:[{data:<?=json_encode($monthly_values)?>,backgroundColor:function(c){const{ctx,chartArea}=c.chart;if(!chartArea)return'rgba(124,58,237,0.6)';const g=ctx.createLinearGradient(0,chartArea.top,0,chartArea.bottom);g.addColorStop(0,'rgba(124,58,237,0.9)');g.addColorStop(1,'rgba(233,30,140,0.4)');return g},borderRadius:8,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(255,255,255,0.04)'},beginAtZero:true}},plugins:{legend:{display:false},tooltip:tp}}});
<?php endif;?>

// Horizontal bar — type breakdown
<?php if(!empty($type_labels)):?>
new Chart(document.getElementById('typeChart'),{type:'bar',data:{labels:<?=json_encode($type_labels)?>,datasets:[{data:<?=json_encode($type_values)?>,backgroundColor:<?=$type_colors?>,borderRadius:6,borderSkipped:false}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},beginAtZero:true},y:{grid:{display:false},ticks:{color:'rgba(255,255,255,0.6)'}}},plugins:{legend:{display:false},tooltip:tp}}});
<?php endif;?>
</script>
<?php endif; ?>
</body>
</html>
