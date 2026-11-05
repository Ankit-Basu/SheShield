<?php
require __DIR__ . '/_layout.php';

$mine = [];
$places = [];
$hasConn = isset($conn) && !$conn->connect_error;
if ($hasConn) {
    $r = $conn->query("SELECT * FROM incidents WHERE status = 'pending' AND user_id = $user_id ORDER BY created_at DESC");
    if ($r) while ($row = $r->fetch_assoc()) $mine[] = $row;

    // Everyone sees counts by place and type. Other people's descriptions stay private.
    $r = $conn->query('SELECT location, incident_type, status, COUNT(*) AS c FROM incidents GROUP BY location, incident_type, status');
    if ($r) while ($row = $r->fetch_assoc()) {
        $key = strtolower(trim((string)$row['location']));
        if ($key === '') continue;
        $places[$key] = $places[$key] ?? ['name' => trim((string)$row['location']), 'open' => 0, 'resolved' => 0, 'types' => []];
        $count = (int)$row['c'];
        $isDone = in_array($row['status'], ['resolved', 'closed'], true);
        $places[$key][$isDone ? 'resolved' : 'open'] += $count;
        if (!$isDone) {
            $type = ucwords(str_replace('_', ' ', (string)$row['incident_type']));
            $places[$key]['types'][$type] = ($places[$key]['types'][$type] ?? 0) + $count;
        }
    }
}
$head = '<link rel="stylesheet" href="../../public/lib/leaflet.css">';
$actions = '<a href="report.php" class="btn btn-primary hide-md">' . icon('plus') . ' New report</a>';
ss_page_open('map', 'Lovely Professional University', 'Campus safety map', $actions, $head);
?>
<?php if (isset($_GET['reported'])): ?>
  <div class="alert" role="status"><?= icon('circle-check') ?><div>Your report was saved. It is listed here while it is pending.</div></div>
<?php endif; ?>
<div class="grid g-map">
  <div class="map-wrap glass" data-reveal>
    <div id="map" class="map" data-lenis-prevent role="application" aria-label="Satellite map of the LPU campus with landmarks and report counts"></div>
    <div class="map-legend">
      <span><span class="dot" style="color:#ff5a4d"></span>Open reports</span>
      <span><span class="dot" style="color:#2ee6a6"></span>All resolved</span>
      <span id="unmappedNote" hidden></span>
    </div>
  </div>
  <aside class="glass card-lg map-side" data-reveal data-lenis-prevent>
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
  </aside>
</div>
<?php
$places_json = json_encode(array_values($places), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
ob_start(); ?>
<script src="../../public/lib/leaflet.js"></script>
<script src="../../public/js/locations.js"></script>
<script src="../../public/js/lpu-campus.js"></script>
<script src="../../public/js/campus-map.js"></script>
<script>
const PLACES = <?= $places_json ?>;
const { map, coords, addLandmarks, escapeHtml } = CampusMap.create('map');
addLandmarks();

// One bubble per place, sized by count, so reports never pile up into an unreadable stack.
const locate = (name) => {
  const key = name.toLowerCase();
  if (coords[key]) return coords[key];
  const typed = key.match(/^(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)$/);
  if (typed) return { lat: +typed[1], lng: +typed[2] };
  const near = Object.keys(coords).find((k) => key.includes(k));
  return near ? coords[near] : null;
};
let unmapped = 0;
PLACES.forEach((p) => {
  const at = locate(p.name);
  if (!at) { unmapped += p.open + p.resolved; return; }
  const open = p.open > 0;
  const total = open ? p.open : p.resolved;
  const size = Math.min(52, 30 + total * 2);
  const icon = L.divIcon({
    className: '',
    html: `<div class="map-count ${open ? 'is-open' : 'is-done'}" style="width:${size}px;height:${size}px">${open ? total : SS.icon('check')}</div>`,
    // Sits above the landmark, so the place name stays readable.
    iconSize: [size, size], iconAnchor: [size / 2, size + 15],
  });
  const types = Object.entries(p.types).map(([t, n]) => `${escapeHtml(t)}: ${n}`).join('<br>');
  L.marker([at.lat, at.lng], { icon }).addTo(map).bindPopup(
    `<b>${escapeHtml(p.name)}</b>${p.open} open, ${p.resolved} resolved${types ? `<br><small>${types}</small>` : ''}`,
  );
});
if (unmapped) {
  const note = document.getElementById('unmappedNote');
  note.hidden = false;
  note.textContent = `${unmapped} without a mapped place`;
}

document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-resolve]');
  if (!b) return;
  SS.busy(b, true, 'Saving');
  try {
    await SS.post('../../api/incidents/resolve.php', { id: Number(b.dataset.resolve) });
    showToast('Report marked as resolved');
    setTimeout(() => location.reload(), 700);
  } catch (err) {
    SS.busy(b, false);
    showToast(err.message, 'error');
  }
});
</script>
<?php ss_page_close('map', ob_get_clean()); ?>
