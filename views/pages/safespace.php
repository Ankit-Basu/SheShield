<?php
require __DIR__ . '/_layout.php';

$ts = 0; $ti = 0; $tw = 0;
if (isset($conn) && !$conn->connect_error) {
    $count = function ($sql) use ($conn) { $r = $conn->query($sql); return $r ? (int)$r->fetch_row()[0] : 0; };
    $ts = $count('SELECT COUNT(*) FROM safe_spaces WHERE TIMESTAMPADD(HOUR, time_active, timestamp) > NOW()');
    $ti = $count("SELECT COUNT(*) FROM incidents WHERE status != 'resolved'");
    $tw = $count("SELECT COUNT(*) FROM escorts WHERE status = 'active' AND verification_status = 'verified'");
}
$head = '<link rel="stylesheet" href="../../public/lib/leaflet.css"><link rel="stylesheet" href="../../public/lib/leaflet.draw.css">';
ss_page_open('safespace', 'Safe spaces', 'Safe spaces', '', $head);
?>
<section class="grid g-3">
  <?php foreach ([
    ['Safe spaces', $ts, 'shield-check', 'Marked by the community'],
    ['Open incidents', $ti, 'triangle-alert', 'Not yet resolved'],
    ['Active walkers', $tw, 'footprints', 'Listed across campus'],
  ] as [$label, $value, $ico, $note]): ?>
    <article class="glass glass-frost stat spot" data-reveal>
      <div class="stat-top"><span><?= e($label) ?></span><?= icon($ico) ?></div>
      <div class="stat-num" <?= $ico === 'shield-check' ? 'id="spaceCount"' : '' ?> data-count="<?= (int)$value ?>"><?= (int)$value ?></div>
      <div class="stat-note"><?= e($note) ?></div>
    </article>
  <?php endforeach; ?>
</section>

<div class="grid g-main">
  <div class="map-wrap glass" data-reveal style="padding:6px">
    <div id="safeMap" class="map" data-lenis-prevent role="application" aria-label="Map of safe spaces"></div>
    <div class="map-legend">
      <span><span class="dot" style="color:#22c55e"></span>Active safe space</span>
      <span><span class="dot" style="color:var(--accent)"></span>Your new zone</span>
    </div>
  </div>

  <form class="glass card-lg form-grid" id="zoneForm" data-reveal>
    <div class="row" style="gap:12px">
      <span class="icon-tile"><?= icon('shield-check') ?></span>
      <div><h3 style="font-size:17px">Mark a safe zone</h3><p class="small muted">Draw it on the map, then describe it.</p></div>
    </div>
    <div class="chips" role="group" aria-label="Drawing tool">
      <button type="button" class="chip" data-draw="polygon" aria-pressed="false"><?= icon('pentagon') ?> Area</button>
      <button type="button" class="chip" data-draw="circle" aria-pressed="false"><?= icon('circle') ?> Circle</button>
      <button type="button" class="chip" data-draw="marker" aria-pressed="false"><?= icon('map-pin') ?> Point</button>
    </div>
    <div class="alert" id="zoneStatus"><?= icon('info') ?><div>Choose a tool above, then draw on the map.</div></div>
    <div class="field"><label class="label" for="zoneName">Name</label><input class="input" id="zoneName" placeholder="Library block, girls hostel gate" maxlength="80" required></div>
    <div class="field"><label class="label" for="zoneDesc">Why it is safe</label><textarea class="input" id="zoneDesc" rows="3" placeholder="Well lit, guard on duty, open late" required></textarea></div>
    <div class="field">
      <span class="label">Active for</span>
      <div class="chips" id="zoneTime" role="group" aria-label="Time active">
        <button type="button" class="chip" data-hours="24" aria-pressed="true">24 hours</button>
        <button type="button" class="chip" data-hours="12" aria-pressed="false">12 hours</button>
        <button type="button" class="chip" data-hours="168" aria-pressed="false">1 week</button>
      </div>
    </div>
    <button class="btn btn-primary btn-lg" id="zoneSave" type="submit"><?= icon('shield-check') ?> Mark as safe zone</button>
  </form>
</div>
<?php ob_start(); ?>
<script src="../../public/lib/leaflet.js"></script>
<script src="../../public/lib/leaflet.draw.js"></script>
<script src="../../public/js/locations.js"></script>
<script src="../../public/js/lpu-campus.js"></script>
<script src="../../public/js/campus-map.js"></script>
<script>
const { map, addLandmarks } = CampusMap.create('safeMap');
addLandmarks();

const accent = getComputedStyle(document.documentElement).getPropertyValue('--aurora-3').trim();
const shape = { color: accent, fillColor: accent, fillOpacity: 0.2, weight: 2 };
const drawn = new L.FeatureGroup().addTo(map);
const options = {
  polygon: { allowIntersection: false, shapeOptions: shape, showArea: false },
  circle: { shapeOptions: shape },
  marker: { icon: L.divIcon({ className: '', html: `<div class="pin" style="background:${accent}">${SS.icon('shield')}</div>`, iconSize: [28, 28], iconAnchor: [14, 14] }) },
};
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const safeIcon = L.divIcon({ className: '', html: `<div class="pin" style="background:#22c55e">${SS.icon('shield-check')}</div>`, iconSize: [28, 28], iconAnchor: [14, 14] });
const spaceLayer = L.layerGroup().addTo(map);

async function loadSpaces() {
  try {
    const res = await fetch('../../api/safespaces/list.php', { credentials: 'same-origin' });
    const list = await res.json();
    spaceLayer.clearLayers();
    list.forEach((s) => {
      const [title, ...rest] = String(s.description).split('\n');
      L.marker([s.latitude, s.longitude], { icon: safeIcon }).addTo(spaceLayer).bindPopup(`<b>${esc(title)}</b>${esc(rest.join(' '))}`);
    });
  } catch (err) {
    showToast('Could not load saved safe spaces.', 'warning');
  }
}
loadSpaces();

let drawer = null;
let coordinates = null;
const statusEl = document.getElementById('zoneStatus');
const setStatus = (ico, text) => { statusEl.innerHTML = `${SS.icon(ico)}<div>${text}</div>`; };

document.querySelectorAll('[data-draw]').forEach((chip) => chip.addEventListener('click', () => {
  document.querySelectorAll('[data-draw]').forEach((c) => c.setAttribute('aria-pressed', String(c === chip)));
  if (drawer) drawer.disable();
  const kind = chip.dataset.draw;
  drawer = kind === 'polygon' ? new L.Draw.Polygon(map, options.polygon)
    : kind === 'circle' ? new L.Draw.Circle(map, options.circle) : new L.Draw.Marker(map, options.marker);
  drawer.enable();
  setStatus('info', kind === 'polygon' ? 'Click to add corners, then click the first corner to finish.' : kind === 'circle' ? 'Press and drag on the map.' : 'Click the spot on the map.');
}));

map.on(L.Draw.Event.CREATED, (e) => {
  drawn.clearLayers();
  drawn.addLayer(e.layer);
  // api/safespaces/save_zone.php takes a circle or polygon; a single point is stored as a small circle.
  if (e.layerType === 'circle') {
    const c = e.layer.getLatLng();
    coordinates = { type: 'circle', center: [c.lat, c.lng], radius: e.layer.getRadius() };
  } else if (e.layerType === 'marker') {
    const c = e.layer.getLatLng();
    coordinates = { type: 'circle', center: [c.lat, c.lng], radius: 30 };
  } else {
    coordinates = { type: 'polygon', points: e.layer.getLatLngs()[0].map((p) => [p.lat, p.lng]) };
  }
  document.querySelectorAll('[data-draw]').forEach((c) => c.setAttribute('aria-pressed', 'false'));
  setStatus('circle-check', 'Zone drawn. Add the details below and save.');
});

document.querySelectorAll('#zoneTime .chip').forEach((chip) => chip.addEventListener('click', () => {
  document.querySelectorAll('#zoneTime .chip').forEach((c) => c.setAttribute('aria-pressed', String(c === chip)));
}));

document.getElementById('zoneForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!coordinates) { showToast('Draw the zone on the map first.', 'warning'); return; }
  const btn = document.getElementById('zoneSave');
  const hours = Number(document.querySelector('#zoneTime [aria-pressed="true"]').dataset.hours);
  const name = document.getElementById('zoneName').value.trim();
  const desc = document.getElementById('zoneDesc').value.trim();
  SS.busy(btn, true, 'Saving');
  try {
    await SS.post('../../api/safespaces/save_zone.php', {
      coordinates: JSON.stringify(coordinates), description: `${name}\n${desc}`, timeActive: hours,
    });
    showToast('Safe zone saved and live on the map.');
    e.target.reset();
    drawn.clearLayers();
    coordinates = null;
    setStatus('info', 'Choose a tool above, then draw on the map.');
    const count = document.getElementById('spaceCount');
    count.textContent = Number(count.textContent) + 1;
    loadSpaces();
  } catch (err) {
    showToast(err.message, 'error');
  }
  SS.busy(btn, false);
});
</script>
<?php ss_page_close('safespace', ob_get_clean()); ?>
