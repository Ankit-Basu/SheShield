<?php
require __DIR__ . '/_layout.php';

$mine = [];
$points = [];
if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT * FROM incidents WHERE status = 'pending' AND user_id = $user_id ORDER BY created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $mine[] = $row;

    $r = $conn->query('SELECT incident_type, description, location, status, created_at FROM incidents ORDER BY created_at DESC LIMIT 300');
    if ($r) while ($row = $r->fetch_assoc()) {
        $points[] = [
            'type' => ucwords(str_replace('_', ' ', $row['incident_type'] ?? 'Incident')),
            'desc' => mb_substr((string)($row['description'] ?? ''), 0, 90),
            'loc' => strtolower(trim((string)($row['location'] ?? ''))),
            'status' => $row['status'] ?? 'pending',
            'date' => date('j M, g:i A', strtotime($row['created_at'] ?? 'now')),
        ];
    }
}
$head = '<link rel="stylesheet" href="../../public/lib/leaflet.css">';
$actions = '<a href="report.php" class="btn btn-primary hide-md">' . icon('plus') . ' New report</a>';
ss_page_open('map', 'Safety map', 'Campus safety map', $actions, $head);
?>
<?php if (isset($_GET['reported'])): ?>
  <div class="alert" role="status"><?= icon('circle-check') ?><div>Your report was saved. It is listed here while it is pending.</div></div>
<?php endif; ?>
<div class="grid g-main">
  <div class="map-wrap glass" data-reveal style="padding:6px">
    <div id="map" class="map" data-lenis-prevent role="application" aria-label="Campus map with landmarks and reported incidents"></div>
    <div class="map-legend" style="left:18px;bottom:18px">
      <span><span class="dot" style="color:#ef4444"></span>Open incident</span>
      <span><span class="dot" style="color:#22c55e"></span>Resolved</span>
      <span><span class="dot" style="color:#a493a0"></span>Landmark</span>
    </div>
  </div>
  <div class="glass card-lg" data-reveal data-lenis-prevent style="max-height:clamp(380px,68dvh,700px);overflow-y:auto">
    <div class="card-head" style="margin-bottom:6px"><div><h3>Your open reports</h3><p><?= count($mine) ?> pending</p></div></div>
    <?php if (!$mine): ?>
      <div class="empty"><?= icon('shield-check') ?><span>You have no open reports.</span></div>
    <?php else: foreach ($mine as $inc):
      $sev = $inc['severity'] ?? 'low';
      $sevCls = $sev === 'high' ? 'badge-danger' : ($sev === 'medium' ? 'badge-warn' : 'badge-info');
    ?>
      <div style="padding:13px 0;border-top:1px solid var(--glass-line-soft)">
        <div class="row-between"><b class="truncate"><?= e(ucwords(str_replace('_', ' ', $inc['incident_type'] ?? 'Incident'))) ?></b><span class="xs muted" style="white-space:nowrap"><?= e(date('j M, g:i A', strtotime($inc['created_at'] ?? 'now'))) ?></span></div>
        <p class="small muted truncate" style="margin:2px 0 9px"><?= e($inc['location'] ?? '') ?></p>
        <div class="row-between">
          <span class="badge <?= $sevCls ?>"><?= e(ucfirst($sev)) ?></span>
          <button class="btn btn-outline btn-sm" data-resolve="<?= (int)$inc['id'] ?>"><?= icon('check') ?> Mark resolved</button>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php
$points_json = json_encode($points, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
ob_start(); ?>
<script src="../../public/lib/leaflet.js"></script>
<script src="../../public/js/locations.js"></script>
<script>
const INCIDENTS = <?= $points_json ?>;
const map = L.map('map').setView([31.2533, 75.705], 16);
map.attributionControl.setPrefix(false);
const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas';
L.tileLayer(esri + '/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles &copy; Esri, HERE, Garmin, &copy; OpenStreetMap contributors', maxNativeZoom: 16, maxZoom: 19 }).addTo(map);
L.tileLayer(esri + '/World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}', { maxNativeZoom: 16, maxZoom: 19 }).addTo(map);
setTimeout(() => map.invalidateSize(), 250);

const pin = (color, name) => L.divIcon({ className: '', html: `<div class="pin" style="background:${color}">${SS.icon(name)}</div>`, iconSize: [28, 28], iconAnchor: [14, 14] });
const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const landmarkIcons = {
  academic: pin('#5b4b57', 'graduation-cap'), residential: pin('#5b4b57', 'house'), commercial: pin('#5b4b57', 'store'),
  entrance: pin('#5b4b57', 'door-open'), recreational: pin('#5b4b57', 'trophy'),
};
const coords = {};
(typeof lpuLocations !== 'undefined' ? lpuLocations : []).forEach((l) => {
  coords[l.name.toLowerCase()] = l;
  L.marker([l.lat, l.lng], { icon: landmarkIcons[l.type] || landmarkIcons.academic }).addTo(map)
    .bindPopup(`<b>${escapeHtml(l.name)}</b>${escapeHtml(l.description || '')}`);
});

const openIcon = pin('#ef4444', 'triangle-alert');
const doneIcon = pin('#22c55e', 'check');
INCIDENTS.forEach((i, n) => {
  const at = coords[i.loc] || { lat: 31.2533, lng: 75.705 };
  // Deterministic spiral offset so reports at one place stay visible and do not move on reload.
  const a = n * 2.4; const d = 0.00012 * Math.sqrt(n + 1);
  L.marker([at.lat + Math.sin(a) * d, at.lng + Math.cos(a) * d], { icon: i.status === 'resolved' ? doneIcon : openIcon }).addTo(map)
    .bindPopup(`<b>${escapeHtml(i.type)}</b>${escapeHtml(i.desc)}<br><small>${escapeHtml(i.status)} · ${escapeHtml(i.date)}</small>`);
});

document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-resolve]');
  if (!b) return;
  SS.busy(b, true, 'Saving');
  try {
    await SS.post('../../incidents/resolve-incident.php', { id: Number(b.dataset.resolve) });
    showToast('Report marked as resolved');
    setTimeout(() => location.reload(), 700);
  } catch (err) {
    SS.busy(b, false);
    showToast(err.message, 'error');
  }
});
</script>
<?php ss_page_close('map', ob_get_clean()); ?>
