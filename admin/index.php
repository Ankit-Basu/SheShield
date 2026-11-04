<?php
require_once __DIR__ . '/../app/middleware/session.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../models/Incident.php';

if (!Session::isLoggedIn() || !Session::get('is_admin')) {
    header('Location: ../pro/login.html');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$incident = new Incident($db);

$count = fn ($sql) => (int)$db->query($sql)->fetchColumn();
$total = $count('SELECT COUNT(*) FROM incidents');
$pending = $count("SELECT COUNT(*) FROM incidents WHERE status = 'pending'");
$resolved = $count("SELECT COUNT(*) FROM incidents WHERE status = 'resolved'");

$filter = in_array($_GET['status'] ?? '', ['pending', 'in_progress', 'resolved', 'closed'], true) ? $_GET['status'] : '';
$incidents = $incident->getAllIncidents(1, 100, $filter ? ['status' => $filter] : []);

function a($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ai($name) { return '<svg class="i" aria-hidden="true"><use href="../public/icons.svg#' . $name . '"/></svg>'; }
$badge = ['pending' => 'badge-warn', 'in_progress' => 'badge-info', 'resolved' => 'badge-good', 'closed' => ''];
$v = filemtime(__DIR__ . '/../public/css/glass.css');
?>
<!DOCTYPE html>
<html lang="en" data-base="../">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin · SheShield</title>
  <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
  <script>try{document.documentElement.dataset.theme=localStorage.getItem('ss-theme')||'dawn'}catch(e){}</script>
  <link rel="stylesheet" href="../public/css/glass.css?v=<?= $v ?>">
</head>
<body>
<div class="ss-backdrop" data-mode="calm" aria-hidden="true"></div>
<div style="width:min(calc(100% - 32px),1240px);margin:0 auto;padding:14px 0 48px;display:grid;gap:16px">
  <header class="topbar glass glass-blur" style="margin:0;top:14px">
    <div class="topbar-title"><span class="eyebrow">SheShield admin</span><h1>Incident reports</h1></div>
    <div class="topbar-actions">
      <span class="small muted hide-md"><?= a(Session::getUserName()) ?></span>
      <a href="../auth/logout.php" class="btn btn-outline btn-sm"><?= ai('log-out') ?> Log out</a>
    </div>
  </header>
  <section class="grid g-3">
    <?php foreach ([['Total reports', $total, 'file-text'], ['Pending', $pending, 'clock'], ['Resolved', $resolved, 'circle-check']] as [$label, $value, $ico]): ?>
      <article class="glass stat spot" data-reveal>
        <div class="stat-top"><span><?= a($label) ?></span><?= ai($ico) ?></div>
        <div class="stat-num" data-count="<?= $value ?>"><?= $value ?></div>
      </article>
    <?php endforeach; ?>
  </section>
  <section class="glass card-lg" data-reveal>
    <div class="row-between wrap" style="margin-bottom:14px">
      <h2 style="font-size:19px">Reports</h2>
      <nav class="tabs" aria-label="Filter by status">
        <?php foreach (['' => 'All', 'pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $key => $label): ?>
          <a class="tab <?= $filter === $key ? 'active' : '' ?>" href="index.php<?= $key ? '?status=' . $key : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>ID</th><th>Reporter</th><th>Type</th><th>Location</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php while ($row = $incidents->fetch(PDO::FETCH_ASSOC)): $status = $row['status'] ?? 'pending'; ?>
            <tr>
              <td class="mono"><?= (int)$row['id'] ?></td>
              <td><?= a(trim($row['reporter_name'] ?? '') ?: 'Anonymous') ?></td>
              <td><?= a(ucwords(str_replace('_', ' ', $row['type'] ?? ''))) ?></td>
              <td><?= a($row['location'] ?? '') ?></td>
              <td style="white-space:nowrap"><?= a(date('j M Y, g:i A', strtotime($row['incident_date'] ?? 'now'))) ?></td>
              <td>
                <label class="sr-only" for="st<?= (int)$row['id'] ?>">Status for report <?= (int)$row['id'] ?></label>
                <select class="input" id="st<?= (int)$row['id'] ?>" data-status="<?= (int)$row['id'] ?>" style="height:34px;width:138px;font-size:13px">
                  <?php foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><a class="btn btn-ghost btn-sm" href="view_incident.php?id=<?= (int)$row['id'] ?>">View <?= ai('arrow-right') ?></a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<script src="../public/lib/gsap.min.js"></script>
<script src="../public/lib/ScrollTrigger.min.js"></script>
<script src="../public/js/sheshield.js?v=<?= $v ?>"></script>
<script>
document.addEventListener('change', async (e) => {
  const s = e.target.closest('[data-status]');
  if (!s) return;
  try {
    await SS.post('../api/incidents/update_status.php', { id: Number(s.dataset.status), status: s.value });
    showToast('Status updated');
  } catch (err) { showToast(err.message, 'error'); }
});
</script>
</body>
</html>
