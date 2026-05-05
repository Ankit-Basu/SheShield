<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__.'/../../app/models/mysqli_db.php';
$user_id=isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:0;
$activePage='map';$today=strtoupper(date('l, F j'));
$incidents=[];
if(isset($conn)&&!$conn->connect_error&&$user_id>0){$r=$conn->query("SELECT * FROM incidents WHERE status='pending' AND user_id=$user_id ORDER BY created_at DESC");if($r)while($row=$r->fetch_assoc())$incidents[]=$row;}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Safety Map — SheShield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
  <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css"/>
  <script src="../../public/js/locations.js?v=3"></script>
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__.'/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div><div class="topbar-date"><?=$today?> — SAFETY MAP</div><h1 class="topbar-title">Campus <span class="gradient-text">Safety Map</span></h1></div>
      <div class="topbar-right"><a href="report.php" class="btn-report">+ New Report</a></div>
    </div>
    <div class="dash-inner">
      <div style="display:grid;grid-template-columns:1fr 320px;gap:16px">
        <div style="position:relative">
          <div id="map"></div>
          <div class="map-legend"><div class="legend-item"><span class="dot green"></span>Safe Space</div><div class="legend-item"><span class="dot red"></span>Incident</div><div class="legend-item"><span class="dot amber"></span>Caution Zone</div></div>
        </div>
        <div class="glass-card" style="max-height:560px;overflow-y:auto">
          <h3 style="font-size:15px;font-weight:600;margin-bottom:16px;display:flex;align-items:center;gap:8px"><i class="fa-solid fa-bell" style="color:#ef4444"></i> Active Incidents</h3>
          <?php if(empty($incidents)):?><p style="font-size:13px;color:rgba(255,255,255,0.4);text-align:center;padding:20px 0">No active incidents</p>
          <?php else:foreach($incidents as $inc):$sev=$inc['severity']??'low';$sc=$sev==='high'?'red':($sev==='medium'?'amber':'green');?>
          <div style="padding:12px;border:1px solid rgba(255,255,255,0.06);border-radius:12px;margin-bottom:8px">
            <div style="display:flex;justify-content:space-between"><span style="font-size:13px;font-weight:500"><?=htmlspecialchars($inc['incident_type']??'Unknown')?></span><span style="font-size:11px;color:rgba(255,255,255,0.3)"><?=date('g:i A',strtotime($inc['created_at']))?></span></div>
            <p style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:4px"><?=htmlspecialchars($inc['location']??'')?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px"><span class="tag-pill <?=$sc?>"><?=ucfirst($sev)?></span><button class="btn-secondary" style="font-size:11px;padding:5px 10px" onclick="markResolved(<?=$inc['id']?>)">Resolve</button></div>
          </div>
          <?php endforeach;endif;?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script>
const map=L.map('map').setView([31.2533,75.7050],15);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',subdomains:'abcd',maxZoom:19}).addTo(map);
setTimeout(()=>map.invalidateSize(),300);

const icons={
  academic:L.divIcon({className:'',html:'<div style="background:#22c55e;padding:6px;border-radius:50%;border:2px solid #fff"><i class="fa-solid fa-graduation-cap" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]}),
  residential:L.divIcon({className:'',html:'<div style="background:#3b82f6;padding:6px;border-radius:50%;border:2px solid #fff"><i class="fa-solid fa-home" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]}),
  commercial:L.divIcon({className:'',html:'<div style="background:#f59e0b;padding:6px;border-radius:50%;border:2px solid #fff"><i class="fa-solid fa-shopping-cart" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]}),
  entrance:L.divIcon({className:'',html:'<div style="background:#7c3aed;padding:6px;border-radius:50%;border:2px solid #fff"><i class="fa-solid fa-door-open" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]}),
  recreational:L.divIcon({className:'',html:'<div style="background:#ef4444;padding:6px;border-radius:50%;border:2px solid #fff"><i class="fa-solid fa-futbol" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]})
};
if(typeof lpuLocations!=='undefined'){lpuLocations.forEach(l=>{L.marker([l.lat,l.lng],{icon:icons[l.type]||icons.academic}).addTo(map).bindPopup('<b>'+l.name+'</b><br>'+l.description)})}

// Incident markers from database
const incidentIcon = L.divIcon({className:'',html:'<div style="background:#ef4444;padding:6px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 12px rgba(239,68,68,0.5);animation:sosPulse 2s infinite"><i class="fa-solid fa-exclamation-triangle" style="color:#fff;font-size:12px"></i></div>',iconSize:[28,28],iconAnchor:[14,14]});
const locCoords = {};
if(typeof lpuLocations!=='undefined'){lpuLocations.forEach(l=>{locCoords[l.name.toLowerCase()]=l})}

<?php
// Get all incidents to show on map
$all_incidents = [];
if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT * FROM incidents ORDER BY created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $all_incidents[] = $row;
}
foreach ($all_incidents as $inc):
  $loc = strtolower(trim($inc['location'] ?? ''));
  $type = htmlspecialchars($inc['incident_type'] ?? 'Unknown');
  $desc = htmlspecialchars(substr($inc['description'] ?? '', 0, 80));
  $status = $inc['status'] ?? 'pending';
  $date = date('M j, g:i A', strtotime($inc['created_at'] ?? 'now'));
  $sc = $status === 'resolved' ? '#22c55e' : ($status === 'pending' ? '#f59e0b' : '#ef4444');
?>
(function(){
  const loc = locCoords['<?= addslashes($loc) ?>'] || {lat:31.2533,lng:75.7050};
  if(loc){
    const m = L.marker([loc.lat + (Math.random()-0.5)*0.001, loc.lng + (Math.random()-0.5)*0.001], {icon: incidentIcon}).addTo(map);
    m.bindPopup('<div style="min-width:180px"><b style="color:#ef4444"><?= ucfirst($type) ?></b><br><span style="font-size:12px;color:#666"><?= $desc ?></span><br><span style="font-size:11px;color:<?= $sc ?>;font-weight:600"><?= ucfirst($status) ?></span> · <span style="font-size:11px;color:#999"><?= $date ?></span></div>');
  }
})();
<?php endforeach; ?>

function markResolved(id){fetch('../../incidents/resolve-incident.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}).then(r=>r.json()).then(d=>{if(d.success){showToast('Incident resolved!','success');setTimeout(()=>location.reload(),1000)}else showToast('Failed','error')}).catch(()=>showToast('Error','error'))}
</script>
</body>
</html>
