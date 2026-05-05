<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$activePage = 'templates';
$today = strtoupper(date('l, F j'));

$templates = [
  ['id'=>1,'cat'=>'Harassment','color'=>'rose','title'=>'Workplace Harassment Complaint','desc'=>'Formal complaint template for reporting workplace harassment including verbal abuse, inappropriate behavior, or hostile work environment.'],
  ['id'=>2,'cat'=>'Stalking','color'=>'violet','title'=>'Stalking Incident Report','desc'=>'Detailed template for documenting stalking behavior patterns, evidence collection, and requesting protective orders.'],
  ['id'=>3,'cat'=>'Cybercrime','color'=>'blue','title'=>'Online Harassment Report','desc'=>'Template for reporting cyberbullying, doxxing, revenge porn, or other forms of digital harassment and abuse.'],
  ['id'=>4,'cat'=>'Harassment','color'=>'rose','title'=>'Street Harassment Report','desc'=>'Report catcalling, following, or threatening behavior in public spaces. Includes witness statement sections.'],
  ['id'=>5,'cat'=>'Domestic','color'=>'red','title'=>'Domestic Violence Report','desc'=>'Confidential template for documenting domestic abuse with safety planning resources and legal aid contacts.'],
  ['id'=>6,'cat'=>'Campus','color'=>'green','title'=>'Campus Safety Complaint','desc'=>'Template for reporting safety issues on campus including poor lighting, broken locks, or unsafe areas.'],
  ['id'=>7,'cat'=>'Stalking','color'=>'violet','title'=>'Digital Stalking Documentation','desc'=>'Track and document digital surveillance, unwanted contact, GPS tracking, and social media monitoring.'],
  ['id'=>8,'cat'=>'Harassment','color'=>'rose','title'=>'Sexual Harassment (POSH)','desc'=>'Formal complaint under POSH Act with ICC filing template, evidence checklist, and timeline documentation.'],
  ['id'=>9,'cat'=>'Cybercrime','color'=>'blue','title'=>'Social Media Abuse Report','desc'=>'Report abusive messages, fake profiles, impersonation, or targeted hate on social media platforms.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates — SheShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="stylesheet" href="../../public/css/dashboard.css?v=3">
</head>
<body class="dash-body">
<div class="dash-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="dash-content">
    <?php include __DIR__.'/blobs.php'; ?>
    <div class="dash-topbar">
      <div>
        <div class="topbar-date"><?= $today ?> — TEMPLATES</div>
        <h1 class="topbar-title">Complaint <span class="gradient-text">Templates</span></h1>
        <p style="font-size:13px;color:rgba(255,255,255,0.4);margin-top:4px">Pre-built templates for faster, more effective reporting</p>
      </div>
      <div class="topbar-right"><a href="report.php" class="btn-report">+ New Report</a></div>
    </div>
    <div class="dash-inner">
      <!-- Filters -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px">
        <div class="filter-bar">
          <button class="filter-pill active" onclick="filterCards('all',this)">All</button>
          <button class="filter-pill" onclick="filterCards('Harassment',this)">Harassment</button>
          <button class="filter-pill" onclick="filterCards('Stalking',this)">Stalking</button>
          <button class="filter-pill" onclick="filterCards('Cybercrime',this)">Cybercrime</button>
          <button class="filter-pill" onclick="filterCards('Domestic',this)">Domestic</button>
          <button class="filter-pill" onclick="filterCards('Campus',this)">Campus</button>
        </div>
        <div class="search-bar" style="width:200px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Search templates..." id="templateSearch" oninput="searchCards(this.value)">
        </div>
      </div>

      <!-- Template Grid -->
      <div class="grid-3" id="templateGrid">
        <?php foreach ($templates as $t): ?>
        <div class="template-card" data-cat="<?= $t['cat'] ?>" data-title="<?= strtolower($t['title']) ?>">
          <span class="tag-pill <?= $t['color'] ?>"><?= $t['cat'] ?></span>
          <h4 style="font-size:15px;font-weight:600"><?= $t['title'] ?></h4>
          <p class="clamp-3" style="font-size:12px;color:rgba(255,255,255,0.5);line-height:1.6"><?= $t['desc'] ?></p>
          <div style="display:flex;gap:8px;margin-top:auto;padding-top:8px">
            <button class="btn-secondary" style="flex:0" onclick="previewTemplate('<?= addslashes($t['title']) ?>','<?= addslashes($t['desc']) ?>')">Preview</button>
            <a href="report.php?template=<?= $t['id'] ?>" class="btn-primary" style="flex:1;text-align:center;text-decoration:none">Use Template →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="previewModal">
  <div class="modal-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h2 style="font-size:18px;font-weight:700">Template Preview</h2>
      <button onclick="closeModal()" style="background:none;border:none;color:rgba(255,255,255,0.5);font-size:24px;cursor:pointer">×</button>
    </div>
    <div id="modalBody" style="font-size:13px;color:rgba(255,255,255,0.6);line-height:1.8;margin-bottom:24px"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button class="btn-secondary" onclick="closeModal()">Close</button>
      <a href="report.php" class="btn-primary" style="text-decoration:none">Use This Template →</a>
    </div>
  </div>
</div>

<script>
function filterCards(cat, btn) {
  document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.template-card').forEach(c => {
    const show = cat === 'all' || c.dataset.cat === cat;
    c.style.opacity = show ? '1' : '0';
    c.style.transform = show ? 'scale(1)' : 'scale(0.95)';
    setTimeout(() => { c.style.display = show ? '' : 'none'; }, 200);
  });
}
function searchCards(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.template-card').forEach(c => {
    c.style.display = c.dataset.title.includes(q) ? '' : 'none';
  });
}
function previewTemplate(title, desc) {
  document.getElementById('modalBody').innerHTML = '<h3 style="font-size:16px;font-weight:600;color:#fff;margin-bottom:12px">'+title+'</h3><p>'+desc+'</p><br><p style="color:rgba(255,255,255,0.3)">Full template content with form fields, legal references, and submission guidelines will be auto-populated when you use this template.</p>';
  document.getElementById('previewModal').classList.add('active');
}
function closeModal() { document.getElementById('previewModal').classList.remove('active'); }
document.querySelectorAll('.template-card').forEach((c,i) => {
  c.style.opacity = '0'; c.style.transform = 'translateY(20px)';
  c.style.transition = 'all 0.4s ease ' + (i*0.07) + 's';
  setTimeout(() => { c.style.opacity = '1'; c.style.transform = 'translateY(0)'; }, 100);
});
</script>
<?php include __DIR__.'/toast.php'; ?>
</body>
</html>
