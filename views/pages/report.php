<?php
require __DIR__ . '/_layout.php';

$reportError = '';
$allowedSeverity = ['low', 'medium', 'high'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['incident_type', 'description', 'date', 'time', 'location'];
    $complete = true;
    foreach ($required as $f) {
        if (empty($_POST[$f])) $complete = false;
    }
    if (!isset($conn) || $conn->connect_error) {
        $reportError = 'Could not reach the database. Please try again.';
    } elseif (!$complete) {
        $reportError = 'Please fill in every required field.';
    } else {
        $stmt = $conn->prepare("INSERT INTO incidents (user_id, incident_type, description, location, date_time, severity, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        if (!$stmt) {
            $reportError = 'Could not save the report. Please try again.';
        } else {
            $dt = $_POST['date'] . ' ' . $_POST['time'];
            $severity = in_array($_POST['severity'] ?? '', $allowedSeverity, true) ? $_POST['severity'] : 'low';
            $stmt->bind_param('isssss', $user_id, $_POST['incident_type'], $_POST['description'], $_POST['location'], $dt, $severity);
            if ($stmt->execute()) {
                header('Location: map.php?reported=1');
                exit();
            }
            $reportError = 'Could not save the report. Please try again.';
            $stmt->close();
        }
    }
}

require __DIR__ . '/_templates_data.php';
$selected = null;
foreach ($SS_TEMPLATES as $t) {
    if ((int)($_GET['template'] ?? 0) === $t['id']) $selected = $t;
}
$postOr = fn ($key, $fallback = '') => $_POST[$key] ?? $fallback;
$typeValue = $postOr('incident_type', $selected['type'] ?? '');
$descValue = $postOr('description', $selected['body'] ?? '');
$locations = ['Block 32','Block 34','Girls Hostel','Boys Hostel','Uni Mall','Main Gate','Library','Sports Complex','Parking Zone','Other'];
$types = ['harassment' => 'Harassment','stalking' => 'Stalking','theft' => 'Theft','assault' => 'Assault','cybercrime' => 'Cybercrime','domestic' => 'Domestic violence','other' => 'Other'];
$contacts = [['LPU Security','1800-102-4431'],['Campus Emergency','+91-1824-517000'],["Women's Helpline",'1091'],['Police','100'],['National Emergency','112']];

ss_page_open('report', 'Reports', 'File a report');
?>
<div class="grid g-main">
  <form method="POST" class="glass card-lg form-grid" data-reveal>
    <div>
      <h2 style="font-size:20px;margin-bottom:4px">What happened?</h2>
      <p class="small muted">Only what you are comfortable sharing.</p>
    </div>
    <?php if ($selected): ?>
      <div class="alert"><?= icon('files') ?><div>Using the <b><?= e($selected['title']) ?></b> template. Answer the prompts in the description in your own words.</div></div>
    <?php endif; ?>
    <?php if ($reportError): ?>
      <div class="alert alert-danger" role="alert"><?= icon('circle-alert') ?><div><?= e($reportError) ?></div></div>
    <?php endif; ?>
    <div class="form-row">
      <div class="field"><label class="label" for="rDate">Date</label><input class="input" type="date" id="rDate" name="date" value="<?= e($postOr('date', date('Y-m-d'))) ?>" max="<?= date('Y-m-d') ?>" required></div>
      <div class="field"><label class="label" for="rTime">Time</label><input class="input" type="time" id="rTime" name="time" value="<?= e($postOr('time')) ?>" required></div>
    </div>
    <div class="form-row">
      <div class="field">
        <label class="label" for="rType">Type</label>
        <select class="input" id="rType" name="incident_type" required>
          <option value="">Choose a type</option>
          <?php foreach ($types as $value => $label): ?><option value="<?= $value ?>" <?= $typeValue === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label class="label" for="rLoc">Location</label>
        <select class="input" id="rLoc" name="location" required>
          <option value="">Choose a location</option>
          <?php foreach ($locations as $loc): ?><option value="<?= e($loc) ?>" <?= $postOr('location') === $loc ? 'selected' : '' ?>><?= e($loc) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <span class="label" id="sevLabel">How serious was it?</span>
      <div class="tabs" role="radiogroup" aria-labelledby="sevLabel" style="width:fit-content">
        <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label): ?>
          <label class="tab" style="cursor:pointer"><input class="sr-only" type="radio" name="severity" value="<?= $value ?>" <?= $postOr('severity', 'low') === $value ? 'checked' : '' ?>><?= $label ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field"><label class="label" for="rDesc">Description</label><textarea class="input" id="rDesc" name="description" rows="<?= $selected ? 10 : 5 ?>" placeholder="What happened, who was involved, and anything that could help." required><?= e($descValue) ?></textarea></div>
    <button class="btn btn-primary btn-lg" type="submit" data-magnet style="justify-self:start"><?= icon('send') ?> Submit report</button>
  </form>

  <div class="stack">
    <div class="glass sos-panel" data-reveal>
      <div class="row-between" style="margin-bottom:6px"><h3 style="font-size:17px">In danger right now?</h3><span class="badge badge-danger"><span class="dot dot-live"></span>24/7</span></div>
      <p class="small muted" style="margin-bottom:14px">Call straight away, and share your location with someone you trust.</p>
      <button class="btn btn-sos btn-lg btn-block" data-sos><?= icon('siren') ?> Open SOS</button>
    </div>
    <div class="glass card" data-reveal>
      <h3 style="font-size:15px;margin-bottom:6px">Helplines</h3>
      <?php foreach ($contacts as [$label, $num]): ?>
        <div class="list-row" style="padding:9px 0">
          <div class="grow"><div class="small muted"><?= e($label) ?></div><div class="mono" style="font-weight:600"><?= e($num) ?></div></div>
          <button type="button" class="btn btn-ghost btn-icon" style="width:34px;height:34px" data-copy="<?= e($num) ?>" aria-label="Copy <?= e($label) ?> number"><?= icon('copy') ?></button>
          <a href="tel:<?= e(str_replace('-', '', $num)) ?>" class="btn btn-outline btn-icon" style="width:34px;height:34px" aria-label="Call <?= e($label) ?>"><?= icon('phone') ?></a>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="glass card" data-reveal>
      <h3 style="font-size:15px;margin-bottom:10px">Staying safe</h3>
      <ul class="small muted" style="display:grid;gap:8px">
        <li class="row" style="align-items:flex-start"><?= icon('check', 'ink-accent') ?>Stay in well-lit, busy areas at night</li>
        <li class="row" style="align-items:flex-start"><?= icon('check', 'ink-accent') ?>Share your live location with someone you trust</li>
        <li class="row" style="align-items:flex-start"><?= icon('check', 'ink-accent') ?>Keep emergency numbers on speed dial</li>
        <li class="row" style="align-items:flex-start"><?= icon('check', 'ink-accent') ?>Trust your instincts. Leave if it feels wrong</li>
      </ul>
    </div>
  </div>
</div>
<?php ob_start(); ?>
<script>
document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-copy]');
  if (!b) return;
  try {
    await navigator.clipboard.writeText(b.dataset.copy);
    showToast('Number copied');
  } catch (err) {
    showToast('Could not copy. The number is ' + b.dataset.copy, 'warning');
  }
});
// Segmented control state for the severity radios
const sevTabs = document.querySelectorAll('.tabs input[name="severity"]');
const syncSev = () => sevTabs.forEach((i) => i.parentElement.classList.toggle('active', i.checked));
sevTabs.forEach((i) => i.addEventListener('change', syncSev));
syncSev();
</script>
<?php ss_page_close('report', ob_get_clean()); ?>
