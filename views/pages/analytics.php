<?php
require __DIR__ . '/_layout.php';

$total = 0; $resolved = 0; $pending = 0; $recent = 0;
$status_counts = []; $type_counts = []; $weekly = []; $monthly = [];

// Every day in the last 7 and every month in the last 6 appears, even at zero,
// so gaps read as quiet periods and not as missing data.
for ($i = 6; $i >= 0; $i--) {
    $weekly[date('Y-m-d', strtotime("-$i day"))] = 0;
}
for ($i = 5; $i >= 0; $i--) {
    $monthly[date('Y-m', strtotime("first day of -$i month"))] = 0;
}

if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT status, COUNT(*) AS c FROM incidents WHERE user_id = $user_id GROUP BY status ORDER BY c DESC");
    if ($r) while ($row = $r->fetch_assoc()) $status_counts[$row['status'] ?: 'pending'] = (int)$row['c'];
    $total = array_sum($status_counts);
    $resolved = $status_counts['resolved'] ?? 0;
    $pending = $status_counts['pending'] ?? 0;

    $r = $conn->query("SELECT incident_type, COUNT(*) AS c FROM incidents WHERE user_id = $user_id GROUP BY incident_type ORDER BY c DESC LIMIT 6");
    if ($r) while ($row = $r->fetch_assoc()) $type_counts[$row['incident_type']] = (int)$row['c'];

    $r = $conn->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM incidents WHERE user_id = $user_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
    if ($r) while ($row = $r->fetch_assoc()) {
        if (isset($weekly[$row['d']])) $weekly[$row['d']] = (int)$row['c'];
    }
    $recent = array_sum($weekly);

    $from = array_key_first($monthly) . '-01';
    $r = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS c FROM incidents WHERE user_id = $user_id AND created_at >= '$from' GROUP BY m");
    if ($r) while ($row = $r->fetch_assoc()) {
        if (isset($monthly[$row['m']])) $monthly[$row['m']] = (int)$row['c'];
    }
}
$rate = $total > 0 ? round($resolved / $total * 100) : 0;
$has_data = $total > 0;

$statusStyle = [
    'resolved' => ['#1fa88f', 'circle-check', 'Resolved'],
    'pending' => ['#e8b53f', 'clock', 'Pending'],
    'in_progress' => ['#5a9fe6', 'activity', 'In progress'],
    'closed' => ['#a3949e', 'x', 'Closed'],
];

function ss_columns($series, $labelFn) {
    $max = max(1, max($series));
    foreach ($series as $key => $value) {
        $height = $value > 0 ? max(4, round($value / $max * 100)) : 1;
        echo '<div class="bar-col' . ($value === 0 ? ' is-zero' : '') . '">';
        echo '<span class="bar-tip">' . $value . ' report' . ($value === 1 ? '' : 's') . '</span>';
        echo '<div class="bar-track"><div class="bar" style="height:' . $height . '%"></div></div>';
        echo '<span class="bar-x">' . e($labelFn($key)) . '</span></div>';
    }
}

$pill = '<span class="badge ' . ($has_data ? 'badge-good' : '') . '"><span class="dot"></span>' . ($has_data ? 'From your reports' : 'No data yet') . '</span>';
ss_page_open('analytics', 'Analytics', 'Your safety analytics', '<span class="hide-md">' . $pill . '</span>');
?>
<section class="grid g-4">
  <?php foreach ([
    ['Total reports', $total, '', 'file-text', 'All time'],
    ['Resolution rate', $rate, '%', 'circle-check', "$resolved of $total resolved"],
    ['This week', $recent, '', 'clock', 'Last 7 days'],
    ['Pending', $pending, '', 'bell', $pending > 0 ? 'Awaiting review' : 'All clear'],
  ] as [$label, $value, $suffix, $ico, $note]): ?>
    <article class="glass glass-frost stat spot" data-reveal>
      <div class="stat-top"><span><?= e($label) ?></span><?= icon($ico) ?></div>
      <div class="stat-num" data-count="<?= (int)$value ?>" data-suffix="<?= $suffix ?>"><?= (int)$value ?><?= $suffix ?></div>
      <div class="stat-note"><?= e($note) ?></div>
    </article>
  <?php endforeach; ?>
</section>

<?php if (!$has_data): ?>
  <section class="glass card-lg empty" data-reveal>
    <?= icon('chart-column') ?>
    <h3 style="color:var(--foreground);font-size:19px">Nothing to chart yet</h3>
    <p style="max-width:46ch">Charts appear here once you file a report. Everything on this page comes from your own reports.</p>
    <a href="report.php" class="btn btn-primary" style="margin-top:8px">File a report <?= icon('arrow-right') ?></a>
  </section>
<?php else: ?>
  <section class="grid g-bento">
    <div class="glass card-lg span-7" data-reveal data-grow="y">
      <div class="card-head"><div><h3>Last 7 days</h3><p>Reports filed per day</p></div></div>
      <div class="bars" role="img" aria-label="Column chart of reports per day. The same data is in the table below."><?php ss_columns($weekly, fn ($d) => date('D', strtotime($d))); ?></div>
    </div>
    <div class="glass card-lg span-5" data-reveal data-grow="x">
      <div class="card-head"><div><h3>Where they stand</h3><p>Share of your reports by status</p></div></div>
      <div class="segbar" role="img" aria-label="Status split, itemised in the legend below">
        <?php foreach ($status_counts as $status => $count): $s = $statusStyle[$status] ?? $statusStyle['closed']; ?>
          <div style="flex:<?= $count ?>;background:<?= $s[0] ?>" title="<?= e($s[2]) ?>: <?= $count ?>"></div>
        <?php endforeach; ?>
      </div>
      <div class="legend">
        <?php foreach ($status_counts as $status => $count): $s = $statusStyle[$status] ?? [$statusStyle['closed'][0], 'info', ucfirst($status)]; ?>
          <div class="legend-item"><span class="swatch" style="background:<?= $s[0] ?>"></span><?= icon($s[1]) ?><span><?= e($s[2]) ?></span><b><?= $count ?> · <?= round($count / $total * 100) ?>%</b></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="glass card-lg span-7" data-reveal data-grow="y">
      <div class="card-head"><div><h3>Last 6 months</h3><p>Reports filed per month</p></div></div>
      <div class="bars" role="img" aria-label="Column chart of reports per month. The same data is in the table below."><?php ss_columns($monthly, fn ($m) => date('M', strtotime($m . '-01'))); ?></div>
    </div>
    <div class="glass card-lg span-5" data-reveal data-grow="x">
      <div class="card-head"><div><h3>By type</h3><p>Most reported first</p></div></div>
      <?php $typeMax = max($type_counts); foreach ($type_counts as $type => $count): ?>
        <div class="hbar">
          <span class="truncate"><?= e(ucwords(str_replace('_', ' ', $type))) ?></span>
          <div class="hbar-track"><div class="hbar-fill" style="width:<?= round($count / $typeMax * 100) ?>%"></div></div>
          <b><?= $count ?></b>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <details class="glass card" data-reveal>
    <summary style="cursor:pointer;font-weight:600">View this data as a table</summary>
    <div class="table-wrap" style="margin-top:12px">
      <table class="table">
        <thead><tr><th>Period</th><th>Reports</th></tr></thead>
        <tbody>
          <?php foreach ($weekly as $d => $v): ?><tr><td><?= e(date('D j M', strtotime($d))) ?></td><td><?= $v ?></td></tr><?php endforeach; ?>
          <?php foreach ($monthly as $m => $v): ?><tr><td><?= e(date('F Y', strtotime($m . '-01'))) ?></td><td><?= $v ?></td></tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
<?php endif; ?>
<?php ss_page_close('analytics'); ?>
