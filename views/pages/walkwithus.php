<?php
require __DIR__ . '/_layout.php';

$user_email = (string)($_SESSION['email'] ?? '');
$user_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: 'Member';
$locations = ['Block 32','Block 34','Girls Hostel','Boys Hostel','Uni Mall','Main Gate','Library','Sports Complex','Parking Zone','Other'];
$areas = ['Block 32-34','Library Area','Uni Mall','Girls Hostel','Sports Complex','Main Gate','Parking Zone','Academic Block'];

$active_walkers = 0;
if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT COUNT(*) FROM escorts WHERE status = 'active'");
    if ($r) $active_walkers = (int)$r->fetch_row()[0];
}

ss_page_open('walkwithus', 'Walk with us', 'Walk with us');
?>
<section class="grid g-3">
  <?php foreach ([
    ['route', 'Tell us your route', 'Share your pickup, destination and time.'],
    ['mail', 'Get a walk ID', 'Your confirmation arrives by email.'],
    ['clock', 'Day or night', 'Request a walk for now or for later.'],
  ] as [$ico, $title, $desc]): ?>
    <article class="glass card spot row" style="gap:14px" data-reveal>
      <span class="icon-tile"><?= icon($ico) ?></span>
      <span><b style="display:block"><?= e($title) ?></b><span class="small muted"><?= e($desc) ?></span></span>
    </article>
  <?php endforeach; ?>
</section>

<section class="grid g-2">
  <form class="glass card-lg form-grid" id="walkRequestForm" data-reveal>
    <div><h2 style="font-size:20px;margin-bottom:4px">Request a walk</h2><p class="small muted">We will email you a confirmation with your walk ID.</p></div>
    <div class="field">
      <label class="label" for="pickupLocation">Pickup</label>
      <select class="input" id="pickupLocation" required><option value="">Where should we meet you?</option><?php foreach ($locations as $loc): ?><option><?= e($loc) ?></option><?php endforeach; ?></select>
    </div>
    <div class="field">
      <label class="label" for="destination">Destination</label>
      <select class="input" id="destination" required><option value="">Where are you going?</option><?php foreach ($locations as $loc): ?><option><?= e($loc) ?></option><?php endforeach; ?></select>
    </div>
    <div class="field"><label class="label" for="preferredTime">When <span class="opt">(leave blank for now)</span></label><input class="input" type="datetime-local" id="preferredTime"></div>
    <button class="btn btn-primary btn-lg" type="submit" id="btnRequestWalk"><?= icon('footprints') ?> Request walk</button>
    <p class="small muted row"><span class="dot <?= $active_walkers ? 'dot-live' : '' ?>" style="color:<?= $active_walkers ? 'var(--good)' : 'var(--muted-foreground)' ?>"></span><?= $active_walkers ? $active_walkers . ' walker' . ($active_walkers === 1 ? '' : 's') . ' active on campus' : 'No walkers are listed as active right now' ?></p>
  </form>

  <form class="glass card-lg form-grid" id="walkerRegForm" data-reveal style="align-content:start">
    <div><h2 style="font-size:20px;margin-bottom:4px">Volunteer as a walker</h2><p class="small muted">Help someone get where they are going.</p></div>
    <div class="form-row">
      <div class="field"><label class="label" for="availFrom">Available from</label><input class="input" type="time" id="availFrom" required></div>
      <div class="field"><label class="label" for="availUntil">Until</label><input class="input" type="time" id="availUntil" required></div>
    </div>
    <div class="field">
      <span class="label">Areas you know</span>
      <div class="chips" id="areaChips" role="group" aria-label="Preferred areas">
        <?php foreach ($areas as $area): ?><button type="button" class="chip" data-area="<?= e($area) ?>" aria-pressed="false"><?= e($area) ?></button><?php endforeach; ?>
      </div>
    </div>
    <button class="btn btn-outline btn-lg" type="submit" id="btnRegisterWalker"><?= icon('hand-heart') ?> Register as walker</button>
  </form>
</section>
<?php ob_start(); ?>
<script>
const WALK_USER = <?= json_encode(['id' => $user_id, 'name' => $user_name, 'email' => $user_email], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
const areas = new Set();
document.getElementById('areaChips').addEventListener('click', (e) => {
  const c = e.target.closest('[data-area]');
  if (!c) return;
  const on = !areas.has(c.dataset.area);
  if (on) areas.add(c.dataset.area); else areas.delete(c.dataset.area);
  c.setAttribute('aria-pressed', String(on));
});
// The endpoint succeeds even if SMTP fails, so report the email outcome truthfully.
const emailNote = (d) => (d.emailSent ? ' A confirmation is in your inbox.' : ' We could not send the confirmation email.');

document.getElementById('walkRequestForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const pickup = document.getElementById('pickupLocation').value;
  const dest = document.getElementById('destination').value;
  if (pickup === dest) { showToast('Pickup and destination need to be different.', 'error'); return; }
  const btn = document.getElementById('btnRequestWalk');
  SS.busy(btn, true, 'Sending');
  try {
    const d = await SS.post('../../api/walks/send_walk_email.php', {
      type: 'walk_request', userId: WALK_USER.id, userName: WALK_USER.name, userEmail: WALK_USER.email,
      pickupLocation: pickup, destination: dest,
      requestTime: document.getElementById('preferredTime').value || new Date().toLocaleString(),
    });
    showToast('Walk request received.' + emailNote(d), d.emailSent ? 'success' : 'warning');
    e.target.reset();
  } catch (err) { showToast(err.message, 'error'); }
  SS.busy(btn, false);
});

document.getElementById('walkerRegForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!areas.size) { showToast('Choose at least one area.', 'error'); return; }
  const btn = document.getElementById('btnRegisterWalker');
  SS.busy(btn, true, 'Registering');
  try {
    const d = await SS.post('../../api/walks/send_walk_email.php', {
      type: 'walker_register', userId: WALK_USER.id, userName: WALK_USER.name, userEmail: WALK_USER.email,
      availableFrom: document.getElementById('availFrom').value, availableUntil: document.getElementById('availUntil').value,
      preferredAreas: [...areas],
    });
    showToast('Thank you for volunteering.' + emailNote(d), d.emailSent ? 'success' : 'warning');
    e.target.reset();
    areas.clear();
    document.querySelectorAll('[data-area]').forEach((c) => c.setAttribute('aria-pressed', 'false'));
  } catch (err) { showToast(err.message, 'error'); }
  SS.busy(btn, false);
});
</script>
<?php ss_page_close('walkwithus', ob_get_clean()); ?>
