<?php
require __DIR__ . '/_layout.php';

$total_reports = 0; $resolved = 0; $pending = 0; $active_walkers = 0; $safe_spaces = 0;
$incidents = []; $type_counts = [];

if (isset($conn) && !$conn->connect_error) {
    $count = function ($sql) use ($conn) {
        $r = $conn->query($sql);
        return $r ? (int)$r->fetch_row()[0] : 0;
    };
    $total_reports = $count("SELECT COUNT(*) FROM incidents WHERE user_id = $user_id");
    $resolved = $count("SELECT COUNT(*) FROM incidents WHERE user_id = $user_id AND status = 'resolved'");
    $pending = $count("SELECT COUNT(*) FROM incidents WHERE user_id = $user_id AND status = 'pending'");
    $active_walkers = $count("SELECT COUNT(*) FROM escorts WHERE status = 'active' AND verification_status = 'verified'");
    $safe_spaces = $count('SELECT COUNT(*) FROM safe_spaces') + $count('SELECT COUNT(*) FROM safe_zone');

    $r = $conn->query("SELECT * FROM incidents WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
    if ($r) while ($row = $r->fetch_assoc()) $incidents[] = $row;

    $r = $conn->query("SELECT incident_type, COUNT(*) AS c FROM incidents WHERE user_id = $user_id GROUP BY incident_type ORDER BY c DESC LIMIT 6");
    if ($r) while ($row = $r->fetch_assoc()) $type_counts[$row['incident_type']] = (int)$row['c'];
}
$type_max = $type_counts ? max($type_counts) : 1;
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$name = trim((string)($_SESSION['first_name'] ?? ''));
$actions = '<a href="report.php" class="btn btn-primary hide-md">' . icon('plus') . ' New report</a>';

ss_page_open('dashboard', strtoupper(date('l, j F')), 'Overview', $actions);
?>
<section class="glass glass-frost dash-hero spot" data-reveal>
  <div>
    <span class="sunclock" data-sunclock></span>
    <h2 class="display" data-split><?= e($greeting) ?><?= $name !== '' ? ', <span class="text-gradient">' . e($name) . '</span>' : '' ?>.</h2>
    <p class="muted"><?php if ($pending > 0): ?>You have <?= $pending ?> report<?= $pending === 1 ? '' : 's' ?> waiting on review.<?php else: ?>Nothing is waiting on you right now.<?php endif; ?></p>
  </div>
</section>

<section class="grid g-4 actions-row">
  <button class="glass action-tile is-sos lift" data-sos data-reveal><span class="icon-tile"><?= icon('siren') ?></span><?= icon('arrow-up-right', 'go') ?><span style="margin-top:auto"><b>Open SOS</b><small>Call and share your location</small></span></button>
  <a href="walkwithus.php" class="glass glass-frost action-tile spot lift" data-reveal><span class="icon-tile"><?= icon('footprints') ?></span><?= icon('arrow-up-right', 'go') ?><span style="margin-top:auto"><b>Request a walk</b><small>Get company on your route</small></span></a>
  <a href="report.php" class="glass glass-frost action-tile spot lift" data-reveal><span class="icon-tile"><?= icon('file-plus') ?></span><?= icon('arrow-up-right', 'go') ?><span style="margin-top:auto"><b>File a report</b><small>Confidential</small></span></a>
  <a href="map.php" class="glass glass-frost action-tile spot lift" data-reveal><span class="icon-tile"><?= icon('map') ?></span><?= icon('arrow-up-right', 'go') ?><span style="margin-top:auto"><b>Campus map</b><small>LPU, by satellite</small></span></a>
</section>

<section class="grid g-4">
  <?php foreach ([
    ['Your reports', $total_reports, 'file-text', 'All time'],
    ['Resolved', $resolved, 'circle-check', 'Of your reports'],
    ['Active walkers', $active_walkers, 'footprints', 'Across campus'],
    ['Safe spaces', $safe_spaces, 'shield-check', 'Marked on the map'],
  ] as [$label, $value, $ico, $note]): ?>
    <article class="glass glass-frost stat spot" data-reveal>
      <div class="stat-top"><span><?= e($label) ?></span><?= icon($ico) ?></div>
      <div class="stat-num" data-count="<?= (int)$value ?>"><?= (int)$value ?></div>
      <div class="stat-note"><?= e($note) ?></div>
    </article>
  <?php endforeach; ?>
</section>

<section class="grid g-bento">
  <div class="glass card-lg span-7" data-reveal>
    <div class="card-head">
      <div><h3>Latest reports</h3><p>Your five most recent</p></div>
      <a href="report.php" class="btn btn-ghost btn-sm">File new <?= icon('arrow-right') ?></a>
    </div>
    <?php if (!$incidents): ?>
      <div class="empty"><?= icon('inbox') ?><span>You have not filed a report yet.</span></div>
    <?php else: foreach ($incidents as $inc):
      $status = $inc['status'] ?? 'pending';
      $cls = $status === 'resolved' ? 'badge-good' : ($status === 'in_progress' ? 'badge-info' : ($status === 'closed' ? '' : 'badge-warn'));
    ?>
      <div class="list-row">
        <span class="icon-tile" style="width:36px;height:36px;font-size:16px"><?= icon('file-text') ?></span>
        <div class="grow">
          <div class="truncate" style="font-weight:600"><?= e(ucwords(str_replace('_', ' ', $inc['incident_type'] ?? 'Incident'))) ?> <span class="muted" style="font-weight:450">· <?= e($inc['location'] ?? '') ?></span></div>
          <div class="truncate small muted"><?= e($inc['description'] ?? '') ?></div>
        </div>
        <span class="badge <?= $cls ?>"><?= e(ucwords(str_replace('_', ' ', $status))) ?></span>
        <span class="xs muted hide-md" style="white-space:nowrap"><?= e(date('j M, g:i A', strtotime($inc['created_at'] ?? 'now'))) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="glass card-lg span-5" data-reveal data-grow="x">
    <div class="card-head"><div><h3>Reports by type</h3><p>Your reports only</p></div></div>
    <?php if (!$type_counts): ?>
      <div class="empty"><?= icon('chart-column') ?><span>This fills in once you file a report.</span></div>
    <?php else: foreach ($type_counts as $type => $count): ?>
      <div class="hbar">
        <span class="truncate"><?= e(ucwords(str_replace('_', ' ', $type))) ?></span>
        <div class="hbar-track"><div class="hbar-fill" style="width:<?= round($count / $type_max * 100) ?>%"></div></div>
        <b><?= $count ?></b>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section>

<?php ss_page_close('dashboard'); ?>
