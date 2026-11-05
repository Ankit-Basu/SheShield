<?php
require_once __DIR__ . '/../app/middleware/session.php';
require_once __DIR__ . '/../app/config/database.php';

if (!Session::isLoggedIn() || !Session::get('is_admin')) {
    header('Location: ../pro/login.html');
    exit();
}
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare(
    "SELECT i.*, u.email AS reporter_email, u.phone AS reporter_phone,
            TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS reporter_name
     FROM incidents i LEFT JOIN users u ON i.user_id = u.id WHERE i.id = :id"
);
$stmt->execute([':id' => (int)$_GET['id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    header('Location: index.php');
    exit();
}

function a($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ai($name) { return '<svg class="i" aria-hidden="true"><use href="../public/icons.svg#' . $name . '"/></svg>'; }
$files = array_filter(explode(',', (string)($row['evidence_file'] ?? '')));
$status = $row['status'] ?? 'pending';
$v = filemtime(__DIR__ . '/../public/css/glass.css');
?>
<!DOCTYPE html>
<html lang="en" data-base="../">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Report #<?= (int)$row['id'] ?> · SheShield admin</title>
  <link rel="icon" type="image/svg+xml" href="../public/assets/favicon.svg">
  <script>try{document.documentElement.dataset.theme=localStorage.getItem('ss-theme')||'dawn'}catch(e){}</script>
  <link rel="stylesheet" href="../public/css/glass.css?v=<?= $v ?>">
</head>
<body>
<div class="ss-backdrop" data-mode="calm" aria-hidden="true"></div>
<div style="width:min(calc(100% - 32px),980px);margin:0 auto;padding:14px 0 48px;display:grid;gap:16px">
  <header class="topbar glass glass-blur" style="margin:0;top:14px">
    <a href="index.php" class="btn btn-ghost btn-icon" aria-label="Back to all reports"><?= ai('arrow-left') ?></a>
    <div class="topbar-title"><span class="eyebrow">Report #<?= (int)$row['id'] ?></span><h1><?= a(ucwords(str_replace('_', ' ', $row['incident_type'] ?? 'Incident'))) ?></h1></div>
    <div class="topbar-actions">
      <label class="sr-only" for="statusSelect">Status</label>
      <select class="input" id="statusSelect" style="height:38px;width:146px;font-size:13px">
        <?php foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $key => $label): ?>
          <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </header>
  <div class="grid g-2">
    <section class="glass card-lg" data-reveal>
      <h2 style="font-size:17px;margin-bottom:8px">Incident</h2>
      <?php foreach ([['Location', $row['location'] ?? ''], ['Happened', date('j M Y, g:i A', strtotime($row['date_time'] ?? 'now'))], ['Reported', date('j M Y, g:i A', strtotime($row['created_at'] ?? 'now'))], ['Severity', ucfirst($row['severity'] ?? 'low')]] as [$label, $value]): ?>
        <div class="list-row"><span class="small muted" style="width:92px;flex:none"><?= $label ?></span><span><?= a($value) ?></span></div>
      <?php endforeach; ?>
    </section>
    <section class="glass card-lg" data-reveal>
      <h2 style="font-size:17px;margin-bottom:8px">Reporter</h2>
      <?php if (empty($row['user_id'])): ?>
        <div class="empty" style="padding:22px 8px"><?= ai('eye-off') ?><span>Filed anonymously.</span></div>
      <?php else: foreach ([['Name', trim($row['reporter_name'] ?? '') ?: 'Not given'], ['Email', $row['reporter_email'] ?? 'Not given'], ['Phone', ($row['reporter_phone'] ?? '') ?: 'Not given']] as [$label, $value]): ?>
        <div class="list-row"><span class="small muted" style="width:92px;flex:none"><?= $label ?></span><span class="truncate"><?= a($value) ?></span></div>
      <?php endforeach; endif; ?>
    </section>
  </div>
  <section class="glass card-lg" data-reveal>
    <h2 style="font-size:17px;margin-bottom:8px">Description</h2>
    <p style="white-space:pre-wrap"><?= a($row['description'] ?? '') ?></p>
  </section>
  <?php if ($files): ?>
    <section class="glass card-lg" data-reveal>
      <h2 style="font-size:17px;margin-bottom:10px">Evidence</h2>
      <div class="chips">
        <?php foreach ($files as $f): $f = basename($f); ?>
          <a class="chip" href="../uploads/evidence/<?= rawurlencode($f) ?>" target="_blank" rel="noopener"><?= ai('file-text') ?> <?= a($f) ?></a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
<script src="../public/lib/gsap.min.js"></script>
<script src="../public/lib/ScrollTrigger.min.js"></script>
<script src="../public/js/sheshield.js?v=<?= $v ?>"></script>
<script>
document.getElementById('statusSelect').addEventListener('change', async (e) => {
  try {
    await SS.post('../api/incidents/update_status.php', { id: <?= (int)$row['id'] ?>, status: e.target.value });
    showToast('Status updated');
  } catch (err) { showToast(err.message, 'error'); }
});
</script>
</body>
</html>
