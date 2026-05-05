<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__.'/../../app/models/mysqli_db.php';
$user_id=isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:0;
$activePage='safespace';$today=strtoupper(date('l, F j'));
$ts=0;$ti=0;
if(isset($conn)&&!$conn->connect_error){
  $r=@mysqli_query($conn,"SELECT COUNT(*) as c FROM safe_spaces");if($r)$ts=mysqli_fetch_assoc($r)['c']??0;
  $r=@mysqli_query($conn,"SELECT COUNT(*) as c FROM incidents");if($r)$ti=mysqli_fetch_assoc($r)['c']??0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Safe Space — SheShield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
  <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__.'/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div><div class="topbar-date"><?=$today?> — SAFE SPACE</div><h1 class="topbar-title">Safe <span class="gradient-text">Spaces</span></h1></div>
      <div class="topbar-right"><a href="report.php" class="btn-report">+ New Report</a></div>
    </div>
    <div class="dash-inner">
      <div class="grid-3 mb-24">
        <div class="stat-card green"><div class="stat-icon green"><i class="fa-solid fa-shield-heart"></i></div><div class="stat-number"><?=$ts?></div><div class="stat-label">Total Safe Spaces</div></div>
        <div class="stat-card rose"><div class="stat-icon rose"><i class="fa-solid fa-file-alt"></i></div><div class="stat-number"><?=$ti?></div><div class="stat-label">Total Incidents</div></div>
        <div class="stat-card amber"><div class="stat-icon amber"><i class="fa-solid fa-users"></i></div><div class="stat-number">0</div><div class="stat-label">Active Users Today</div></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 340px;gap:16px">
        <div style="position:relative">
          <div id="safeMap"></div>
          <div class="map-legend"><div class="legend-item"><span class="dot green"></span>Safe Space</div><div class="legend-item"><span class="dot red"></span>Incident</div><div class="legend-item"><span class="dot amber"></span>Caution</div></div>
        </div>
        <div class="glass-card">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <div style="font-size:24px">🛡️</div>
            <div><h3 style="font-size:16px;font-weight:700;color:#f472b6">Mark Safe Zone</h3><p style="font-size:11px;color:rgba(255,255,255,0.4)">Draw on the map to define a safe area</p></div>
          </div>
          <div style="display:flex;gap:8px;margin-bottom:14px">
            <button class="draw-chip active" onclick="startDraw('polygon',this)" style="padding:8px 14px;border-radius:10px;font-size:12px;border:1px solid #22c55e;background:rgba(34,197,94,0.1);color:#4ade80;cursor:pointer;font-family:inherit">▰ Polygon</button>
            <button class="draw-chip" onclick="startDraw('circle',this)" style="padding:8px 14px;border-radius:10px;font-size:12px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.5);cursor:pointer;font-family:inherit">⊙ Circle</button>
            <button class="draw-chip" onclick="startDraw('marker',this)" style="padding:8px 14px;border-radius:10px;font-size:12px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.04);color:rgba(255,255,255,0.5);cursor:pointer;font-family:inherit">📍 Pin</button>
          </div>
          <div id="zone-drawn-status" style="padding:16px;border:1px dashed rgba(255,255,255,0.1);border-radius:12px;text-align:center;font-size:12px;color:rgba(255,255,255,0.3);margin-bottom:14px">No zone marked yet. Use tools above →</div>
          <input type="hidden" id="zone-coords">
          <div style="display:flex;flex-direction:column;gap:12px">
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block;font-weight:600">Zone Name</label><input class="premium-input" placeholder="e.g. Library Block, Girls Hostel"></div>
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block;font-weight:600">Description</label><textarea class="premium-input" rows="3" placeholder="Why is this a safe space?"></textarea></div>
            <div>
              <label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block;font-weight:600">Time Active</label>
              <div style="display:flex;gap:6px;flex-wrap:wrap" id="timeChips">
                <button type="button" class="filter-pill active" onclick="selectTime(this)">24 Hours</button>
                <button type="button" class="filter-pill" onclick="selectTime(this)">Daytime</button>
                <button type="button" class="filter-pill" onclick="selectTime(this)">Custom</button>
              </div>
            </div>
            <button class="btn-primary" style="padding:12px;font-size:13px;border-radius:12px" onclick="showToast('Safe zone submitted!','success')">🛡️ Mark as Safe Zone</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
const map=L.map('safeMap').setView([31.2533,75.7050],15);
L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',{attribution:'© OpenStreetMap © CARTO',subdomains:'abcd',maxZoom:19}).addTo(map);
setTimeout(()=>map.invalidateSize(),300);

const drawnItems=new L.FeatureGroup();map.addLayer(drawnItems);
const drawControl=new L.Control.Draw({position:'topright',draw:{polygon:{allowIntersection:false,shapeOptions:{color:'#22c55e',fillColor:'#22c55e',fillOpacity:0.15,weight:2,dashArray:'6 4'},showArea:true},circle:{shapeOptions:{color:'#e91e8c',fillColor:'#e91e8c',fillOpacity:0.1,weight:2}},rectangle:false,polyline:false,marker:{icon:L.divIcon({className:'',html:'<div style="background:linear-gradient(135deg,#e91e8c,#7c3aed);width:28px;height:28px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 4px 12px rgba(233,30,140,0.5)"></div>',iconSize:[28,28],iconAnchor:[14,28]})},circlemarker:false},edit:{featureGroup:drawnItems,remove:true}});
map.addControl(drawControl);

map.on(L.Draw.Event.CREATED,function(e){const layer=e.layer;drawnItems.addLayer(layer);let coords;if(e.layerType==='circle'){coords={type:'circle',center:layer.getLatLng(),radius:layer.getRadius()}}else{coords={type:e.layerType,coordinates:layer.toGeoJSON().geometry}}document.getElementById('zone-coords').value=JSON.stringify(coords);document.getElementById('zone-drawn-status').innerHTML='<span style="color:#22c55e">✓ Zone marked — '+e.layerType+' drawn</span>';showToast('Zone drawn! Fill in details →','success')});

let currentDrawer=null;
function startDraw(type,btn){
  document.querySelectorAll('.draw-chip').forEach(c=>{c.style.borderColor='rgba(255,255,255,0.08)';c.style.background='rgba(255,255,255,0.04)';c.style.color='rgba(255,255,255,0.5)'});
  btn.style.borderColor='#22c55e';btn.style.background='rgba(34,197,94,0.1)';btn.style.color='#4ade80';
  if(currentDrawer)currentDrawer.disable();
  if(type==='polygon')currentDrawer=new L.Draw.Polygon(map,drawControl.options.draw.polygon);
  else if(type==='circle')currentDrawer=new L.Draw.Circle(map,drawControl.options.draw.circle);
  else currentDrawer=new L.Draw.Marker(map,drawControl.options.draw.marker);
  currentDrawer.enable();
}
function selectTime(btn){document.querySelectorAll('#timeChips .filter-pill').forEach(p=>p.classList.remove('active'));btn.classList.add('active')}
</script>
</body>
</html>
