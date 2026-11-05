<?php
require __DIR__ . '/_layout.php';
require __DIR__ . '/_templates_data.php';

$cats = array_values(array_unique(array_column($SS_TEMPLATES, 'cat')));
$badge = ['Harassment' => 'badge-primary', 'Stalking' => 'badge-info', 'Cybercrime' => 'badge-warn', 'Domestic' => 'badge-danger', 'Campus' => 'badge-good'];

ss_page_open('templates', 'Templates', 'Complaint templates');
?>
<section class="row-between wrap" data-reveal>
  <div class="tabs" role="tablist" aria-label="Filter templates">
    <button class="tab active" data-filter="all" role="tab" aria-selected="true">All</button>
    <?php foreach ($cats as $cat): ?><button class="tab" data-filter="<?= e($cat) ?>" role="tab" aria-selected="false"><?= e($cat) ?></button><?php endforeach; ?>
  </div>
  <div class="input-wrap" style="width:min(100%,280px)">
    <?= icon('search') ?><input class="input" id="tplSearch" type="search" placeholder="Search templates" aria-label="Search templates">
  </div>
</section>
<section class="grid g-3" id="tplGrid">
  <?php foreach ($SS_TEMPLATES as $t): ?>
    <article class="glass card spot tpl" data-cat="<?= e($t['cat']) ?>" data-title="<?= e(strtolower($t['title'] . ' ' . $t['desc'])) ?>" data-reveal style="display:flex;flex-direction:column;gap:10px">
      <span><span class="badge <?= $badge[$t['cat']] ?? '' ?>"><?= e($t['cat']) ?></span></span>
      <h3 style="font-size:17px"><?= e($t['title']) ?></h3>
      <p class="small muted" style="flex:1"><?= e($t['desc']) ?></p>
      <div class="row">
        <button class="btn btn-outline btn-sm" data-preview="<?= (int)$t['id'] ?>">Preview</button>
        <a class="btn btn-primary btn-sm" style="flex:1" href="report.php?template=<?= (int)$t['id'] ?>">Use template <?= icon('arrow-right') ?></a>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<div class="empty" id="tplEmpty" hidden><?= icon('search') ?><span>No templates match that search.</span></div>

<div class="dialog" id="tplDialog" role="dialog" aria-modal="true" aria-labelledby="tplTitle">
  <div class="dialog-card wide glass glass-blur">
    <button class="btn btn-ghost btn-icon dialog-close" data-dialog-close aria-label="Close preview"><?= icon('x') ?></button>
    <span class="eyebrow">Template preview</span>
    <h2 id="tplTitle" style="font-size:22px;margin:6px 40px 12px 0"></h2>
    <pre id="tplBody" style="font-family:inherit;white-space:pre-wrap;color:var(--muted-foreground);margin-bottom:18px"></pre>
    <div class="row" style="justify-content:flex-end">
      <button class="btn btn-outline" data-dialog-close>Close</button>
      <a class="btn btn-primary" id="tplUse" href="report.php">Use this template <?= icon('arrow-right') ?></a>
    </div>
  </div>
</div>
<?php ob_start(); ?>
<script>
const TEMPLATES = <?= json_encode(array_column($SS_TEMPLATES, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>;
let tplCat = 'all';
const applyFilter = () => {
  const q = document.getElementById('tplSearch').value.trim().toLowerCase();
  let shown = 0;
  document.querySelectorAll('.tpl').forEach((c) => {
    const match = (tplCat === 'all' || c.dataset.cat === tplCat) && c.dataset.title.includes(q);
    c.style.display = match ? 'flex' : 'none';
    if (match) shown += 1;
  });
  document.getElementById('tplEmpty').hidden = shown > 0;
  ScrollTrigger.refresh();
};
document.querySelectorAll('[data-filter]').forEach((b) => b.addEventListener('click', () => {
  document.querySelectorAll('[data-filter]').forEach((x) => { x.classList.toggle('active', x === b); x.setAttribute('aria-selected', String(x === b)); });
  tplCat = b.dataset.filter;
  applyFilter();
}));
document.getElementById('tplSearch').addEventListener('input', applyFilter);
document.addEventListener('click', (e) => {
  const b = e.target.closest('[data-preview]');
  if (!b) return;
  const t = TEMPLATES[b.dataset.preview];
  document.getElementById('tplTitle').textContent = t.title;
  document.getElementById('tplBody').textContent = t.body;
  document.getElementById('tplUse').href = 'report.php?template=' + t.id;
  SS.dialog.open('tplDialog');
});
</script>
<?php ss_page_close('templates', ob_get_clean()); ?>
