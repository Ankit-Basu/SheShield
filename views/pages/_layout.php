<?php
// Shared dashboard shell. Every page calls ss_page_open() / ss_page_close().
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';

// Greetings and dates should match campus time, not the server's zone.
date_default_timezone_set('Asia/Kolkata');

// Dashboard pages require an account: send signed-out visitors to sign in.
if (empty($_SESSION['user_id'])) {
    header('Location: ../../pro/login.html');
    exit();
}
$user_id = (int)$_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function icon($name, $extra = '') {
    return '<svg class="i ' . $extra . '" aria-hidden="true"><use href="../../public/icons.svg#' . $name . '"/></svg>';
}

function ss_assets_version() {
    return filemtime(__DIR__ . '/../../public/css/glass.css') . '-' . filemtime(__DIR__ . '/../../public/js/sheshield.js');
}

function ss_head($title, $extraHead = '') {
    $v = ss_assets_version();
    ?>
<!DOCTYPE html>
<html lang="en" data-base="../../">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · SheShield</title>
  <meta name="theme-color" content="#170d17">
  <link rel="icon" type="image/svg+xml" href="../../assets/favicon.svg">
  <script>try{document.documentElement.dataset.theme=localStorage.getItem('ss-theme')||'dawn'}catch(e){}</script>
  <link rel="preload" href="../../fonts/Satoshi_Complete/Fonts/WEB/fonts/Satoshi-Variable.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="../../fonts/Clash%20Display/Fonts/WEB/fonts/ClashDisplay-Semibold.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="../../public/css/glass.css?v=<?= $v ?>">
  <?= $extraHead ?>
</head>
<body>
<div class="ss-backdrop" data-mode="calm" aria-hidden="true">
  <span class="orb" style="width:260px;height:260px;right:5%;top:15%"></span>
  <span class="orb orb-b" style="width:200px;height:200px;left:38%;top:58%"></span>
</div>
    <?php
}

function ss_page_open($activePage, $eyebrow, $titleHtml, $actionsHtml = '', $extraHead = '') {
    global $conn;
    ss_head(trim(strip_tags($titleHtml)), $extraHead);
    echo '<div class="shell">';
    include __DIR__ . '/sidebar.php';
    ?>
  <div class="ss-main">
    <header class="topbar glass glass-blur">
      <button class="btn btn-ghost btn-icon only-md" data-side-toggle aria-label="Open menu"><?= icon('menu') ?></button>
      <div class="topbar-title">
        <span class="eyebrow"><?= e($eyebrow) ?></span>
        <h1><?= $titleHtml ?></h1>
      </div>
      <div class="topbar-actions">
        <?= $actionsHtml ?>
        <button class="btn btn-sos" data-sos data-magnet><?= icon('siren') ?> SOS</button>
      </div>
    </header>
    <main class="page">
    <?php
}

function ss_page_close($activePage = '', $scripts = '') {
    $v = ss_assets_version();
    ?>
    </main>
  </div>
</div>
<nav class="dock glass glass-blur" aria-label="Quick navigation">
  <a href="dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"><?= icon('house') ?>Home</a>
  <a href="map.php" class="<?= $activePage === 'map' ? 'active' : '' ?>"><?= icon('map') ?>Map</a>
  <button class="dock-sos" data-sos aria-label="Open emergency SOS"><?= icon('siren') ?></button>
  <a href="report.php" class="<?= $activePage === 'report' ? 'active' : '' ?>"><?= icon('file-text') ?>Report</a>
  <a href="walkwithus.php" class="<?= $activePage === 'walkwithus' ? 'active' : '' ?>"><?= icon('footprints') ?>Walk</a>
</nav>
<script src="../../public/lib/gsap.min.js"></script>
<script src="../../public/lib/ScrollTrigger.min.js"></script>
<script src="../../public/lib/lenis.min.js"></script>
<script src="../../public/js/sheshield.js?v=<?= $v ?>"></script>
<?= $scripts ?>
</body>
</html>
    <?php
}
