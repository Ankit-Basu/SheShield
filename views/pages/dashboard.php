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
    $active_walkers = $count("SELECT COUNT(*) FROM escorts WHERE status = 'active'");
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
<section class="glass card-lg spot" data-reveal>
  <div class="row-between wrap">
    <div>
      <h2 class="display" style="font-size:clamp(30px,4.4vw,54px);margin-bottom:8px" data-split><?= e($greeting) ?><?= $name !== '' ? ', <span class="text-gradient">' . e($name) . '</span>' : '' ?>.</h2>
      <p class="muted"><?php if ($pending > 0): ?>You have <?= $pending ?> report<?= $pending === 1 ? '' : 's' ?> waiting on review.<?php else: ?>Nothing is waiting on you right now.<?php endif; ?></p>
    </div>
    <div class="row wrap">
      <a href="walkwithus.php" class="btn btn-outline btn-lg" data-magnet><?= icon('footprints') ?> Request a walk</a>
      <button class="btn btn-sos btn-lg" data-sos data-magnet><?= icon('siren') ?> Open SOS</button>
    </div>
  </div>
</section>

<section class="grid g-4">
  <?php foreach ([
    ['Your reports', $total_reports, 'file-text', 'All time'],
    ['Resolved', $resolved, 'circle-check', 'Of your reports'],
    ['Active walkers', $active_walkers, 'footprints', 'Across campus'],
    ['Safe spaces', $safe_spaces, 'shield-check', 'Marked on the map'],
  ] as [$label, $value, $ico, $note]): ?>
    <article class="glass stat spot" data-reveal>
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

<section class="grid g-3">
  <?php foreach ([
    ['analytics.php', 'chart-column', 'Analytics', 'Trends across your reports.'],
    ['map.php', 'map', 'Safety map', 'Campus landmarks and reported incidents.'],
    ['safespace.php', 'shield-check', 'Safe spaces', 'Find or mark somewhere safe.'],
  ] as [$href, $ico, $title, $desc]): ?>
    <a href="<?= $href ?>" class="glass card spot lift row" style="gap:14px" data-reveal>
      <span class="icon-tile"><?= icon($ico) ?></span>
      <span style="flex:1;min-width:0"><b style="display:block"><?= e($title) ?></b><span class="small muted"><?= e($desc) ?></span></span>
      <?= icon('arrow-up-right', 'muted') ?>
    </a>
  <?php endforeach; ?>
</section>
<?php ss_page_close('dashboard'); ?>
