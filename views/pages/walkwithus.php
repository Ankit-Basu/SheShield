<?php
require __DIR__ . '/_layout.php';

$locations = ['Block 32','Block 34','Girls Hostel','Boys Hostel','Uni Mall','Main Gate','Library','Sports Complex','Parking Zone','Other'];
$areas = ['Block 32-34','Library Area','Uni Mall','Girls Hostel','Sports Complex','Main Gate','Parking Zone','Academic Block'];
$user_email = (string)($_SESSION['email'] ?? '');

$active_walkers = 0;
$walks = [];
$profile = null;
if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT COUNT(*) FROM escorts WHERE status = 'active' AND verification_status = 'verified'");
    if ($r) $active_walkers = (int)$r->fetch_row()[0];

    $stmt = $conn->prepare(
        "SELECT w.walk_id, w.pickup_location, w.destination, w.request_time, w.status, e.name AS walker_name, e.phone AS walker_phone
         FROM walk_requests w LEFT JOIN escorts e ON e.escort_id COLLATE utf8mb4_unicode_ci = w.escort_id COLLATE utf8mb4_unicode_ci
         WHERE w.user_id = ? ORDER BY w.request_time DESC LIMIT 8"
    );
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $walks[] = $row;
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT * FROM escorts WHERE email = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $user_email);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }
}
$myAreas = $profile ? (json_decode((string)$profile['preferred_areas'], true) ?: []) : [];
$val = fn ($key, $fallback = '') => e($profile[$key] ?? $fallback);
$statusLabel = ['pending' => ['Finding a walker', 'badge-warn'], 'accepted' => ['Walker assigned', 'badge-good'], 'completed' => ['Completed', ''], 'cancelled' => ['Cancelled', ''], 'rejected' => ['Declined', 'badge-danger']];
$verifyLabel = ['pending' => ['Waiting for admin verification', 'badge-warn'], 'verified' => ['Verified walker', 'badge-good'], 'rejected' => ['Not approved', 'badge-danger']];

ss_page_open('walkwithus', 'Walk with us', 'Walk with us');
?>
<section class="grid g-2">
  <form class="glass glass-frost card-lg form-grid" id="walkRequestForm" data-reveal style="align-content:start">
    <div class="row-between" style="align-items:flex-start">
      <div><h2 class="card-title">Request a walk</h2><p class="small muted">We save it right away and match you with a verified walker.</p></div>
      <span class="badge <?= $active_walkers ? 'badge-good' : '' ?>" style="flex:none"><span class="dot <?= $active_walkers ? 'dot-live' : '' ?>"></span><?= $active_walkers ?> available</span>
    </div>
    <div class="form-row">
      <div class="field">
        <label class="label" for="pickupLocation">Pickup</label>
        <select class="input" id="pickupLocation" required><option value="">Choose a place</option><?php foreach ($locations as $loc): ?><option><?= e($loc) ?></option><?php endforeach; ?></select>
      </div>
      <div class="field">
        <label class="label" for="destination">Destination</label>
        <select class="input" id="destination" required><option value="">Choose a place</option><?php foreach ($locations as $loc): ?><option><?= e($loc) ?></option><?php endforeach; ?></select>
      </div>
    </div>
    <div class="field"><label class="label" for="preferredTime">When <span class="opt">(leave blank for now)</span></label><input class="input" type="datetime-local" id="preferredTime"></div>
    <button class="btn btn-primary btn-lg" type="submit" id="btnRequestWalk"><?= icon('footprints') ?> Request walk</button>
  </form>

  <section class="glass card-lg" data-reveal>
    <div class="card-head"><div><h2 class="card-title">Your walks</h2><p>Most recent first</p></div></div>
    <?php if (!$walks): ?>
      <div class="empty"><?= icon('route') ?><span>Your walk requests will appear here.</span></div>
    <?php else: foreach ($walks as $w): [$label, $cls] = $statusLabel[$w['status']] ?? [ucfirst($w['status']), '']; ?>
      <div class="list-row">
        <span class="icon-tile" style="width:38px;height:38px;font-size:16px"><?= icon('route') ?></span>
        <div class="grow">
          <div class="truncate" style="font-weight:600"><?= e($w['pickup_location']) ?> <?= icon('arrow-right', 'muted') ?> <?= e($w['destination']) ?></div>
          <div class="small muted truncate">
            <?= e(date('j M, g:i A', strtotime($w['request_time']))) ?>
            <?php if (!empty($w['walker_name'])): ?> · <?= e(explode(' ', $w['walker_name'])[0]) ?><?php if (!empty($w['walker_phone']) && $w['status'] === 'accepted'): ?> · <a href="tel:<?= e($w['walker_phone']) ?>" style="text-decoration:underline">Call</a><?php endif; ?><?php endif; ?>
          </div>
        </div>
        <span class="badge <?= $cls ?>"><?= e($label) ?></span>
        <?php if (in_array($w['status'], ['pending', 'accepted'], true)): ?>
          <button class="btn btn-ghost btn-icon" style="width:32px;height:32px" data-cancel="<?= e($w['walk_id']) ?>" aria-label="Cancel this walk" title="Cancel"><?= icon('x') ?></button>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </section>
</section>

<form class="glass card-lg form-grid" id="walkerRegForm" data-reveal>
  <div class="row-between wrap">
    <div><h2 class="card-title">Volunteer as a walker</h2><p class="small muted">An admin checks your ID before you are matched with anyone.</p></div>
    <?php if ($profile): [$vLabel, $vCls] = $verifyLabel[$profile['verification_status']] ?? $verifyLabel['pending']; ?>
      <span class="badge <?= $vCls ?>"><?= icon($profile['verification_status'] === 'verified' ? 'badge-check' : 'clock') ?> <?= e($vLabel) ?></span>
    <?php endif; ?>
  </div>
  <div class="grid g-4" style="gap:12px">
    <div class="field"><label class="label" for="wPhone">Phone</label><input class="input" type="tel" id="wPhone" maxlength="15" value="<?= $val('phone') ?>" required></div>
    <div class="field">
      <label class="label" for="wType">You are</label>
      <select class="input" id="wType" required>
        <?php foreach (['student' => 'Student', 'staff' => 'Staff', 'security' => 'Security'] as $k => $l): ?><option value="<?= $k ?>" <?= ($profile['type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="label" for="wGender">Gender</label>
      <select class="input" id="wGender" required>
        <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $l): ?><option value="<?= $k ?>" <?= ($profile['gender'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="label" for="wIdType">ID type</label>
      <select class="input" id="wIdType" required>
        <?php foreach (['University ID', 'Staff ID', 'Government ID'] as $l): ?><option <?= ($profile['id_proof_type'] ?? '') === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label class="label" for="wIdNum">ID number</label><input class="input" id="wIdNum" autocomplete="off" value="<?= $val('id_proof_number') ?>" required></div>
    <div class="field"><label class="label" for="availFrom">Available from</label><input class="input" type="time" id="availFrom" value="<?= e(substr((string)($profile['available_from'] ?? ''), 0, 5)) ?>" required></div>
    <div class="field"><label class="label" for="availUntil">Until</label><input class="input" type="time" id="availUntil" value="<?= e(substr((string)($profile['available_to'] ?? ''), 0, 5)) ?>" required></div>
  </div>
  <div class="field">
    <span class="label">Areas you know</span>
    <div class="chips" id="areaChips" role="group" aria-label="Preferred areas">
      <?php foreach ($areas as $area): ?><button type="button" class="chip" data-area="<?= e($area) ?>" aria-pressed="<?= in_array($area, $myAreas, true) ? 'true' : 'false' ?>"><?= e($area) ?></button><?php endforeach; ?>
    </div>
  </div>
  <button class="btn btn-outline btn-lg" type="submit" id="btnRegisterWalker" style="justify-self:start"><?= icon('hand-heart') ?> <?= $profile ? 'Update my details' : 'Register as walker' ?></button>
</form>
<?php ob_start(); ?>
<script>
const areas = new Set(<?= json_encode($myAreas, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>);
document.getElementById('areaChips').addEventListener('click', (e) => {
  const c = e.target.closest('[data-area]');
  if (!c) return;
  const on = !areas.has(c.dataset.area);
  if (on) areas.add(c.dataset.area); else areas.delete(c.dataset.area);
  c.setAttribute('aria-pressed', String(on));
});
const val = (id) => document.getElementById(id).value.trim();

document.getElementById('walkRequestForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const pickup = val('pickupLocation');
  const destination = val('destination');
  if (pickup === destination) { showToast('Pickup and destination need to be different.', 'error'); return; }
  const btn = document.getElementById('btnRequestWalk');
  SS.busy(btn, true, 'Saving');
  try {
    const d = await SS.post('../../api/walks/create_request.php', { pickupLocation: pickup, destination, requestTime: val('preferredTime') });
    // Saving is what matters. The email is a courtesy and may fail without losing the request.
    SS.post('../../api/walks/send_walk_email.php', { type: 'walk_request', walkId: d.walkId, pickupLocation: pickup, destination, requestTime: d.requestTime }).catch(() => {});
    showToast(`${d.message} Your walk ID is ${d.walkId}.`);
    setTimeout(() => location.reload(), 1400);
  } catch (err) {
    showToast(err.message, 'error');
    SS.busy(btn, false);
  }
});

document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-cancel]');
  if (!b) return;
  SS.busy(b, true, '');
  try {
    await SS.post('../../api/walks/cancel_request.php', { walkId: b.dataset.cancel });
    showToast('Walk cancelled.');
    setTimeout(() => location.reload(), 700);
  } catch (err) {
    SS.busy(b, false);
    showToast(err.message, 'error');
  }
});

document.getElementById('walkerRegForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!areas.size) { showToast('Choose at least one area.', 'error'); return; }
  const btn = document.getElementById('btnRegisterWalker');
  SS.busy(btn, true, 'Saving');
  try {
    const d = await SS.post('../../api/walks/register_walker.php', {
      phone: val('wPhone'), type: val('wType'), gender: val('wGender'), idProofType: val('wIdType'), idProofNumber: val('wIdNum'),
      availableFrom: val('availFrom'), availableUntil: val('availUntil'), preferredAreas: [...areas],
    });
    showToast(d.message);
    setTimeout(() => location.reload(), 1400);
  } catch (err) {
    showToast(err.message, 'error');
    SS.busy(btn, false);
  }
});
</script>
<?php ss_page_close('walkwithus', ob_get_clean()); ?>
