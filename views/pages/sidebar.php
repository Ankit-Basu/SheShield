<?php
// Shared sidebar component for all dashboard pages
// Usage: $activePage = 'dashboard'; include __DIR__ . '/sidebar.php';
if (!isset($activePage)) $activePage = '';

// Get user data from session (set during login via Session::set)
$firstName = isset($_SESSION['first_name']) ? trim(htmlspecialchars($_SESSION['first_name'])) : '';
$lastName = isset($_SESSION['last_name']) ? trim(htmlspecialchars($_SESSION['last_name'])) : '';
$userEmail = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';

// Fallback: if session is empty but user_id exists, load from DB
if (empty($firstName) && isset($_SESSION['user_id']) && isset($conn) && !$conn->connect_error) {
    $_uid = (int)$_SESSION['user_id'];
    $_ur = @$conn->query("SELECT first_name, last_name, full_name, email FROM users WHERE id = $_uid LIMIT 1");
    if ($_ur && $_urow = $_ur->fetch_assoc()) {
        $firstName = htmlspecialchars($_urow['first_name'] ?? '');
        $lastName = htmlspecialchars($_urow['last_name'] ?? '');
        if ($firstName === '' && !empty($_urow['full_name'])) {
            $parts = explode(' ', trim($_urow['full_name']), 2);
            $firstName = htmlspecialchars($parts[0] ?? '');
            $lastName = htmlspecialchars($parts[1] ?? $lastName);
        }
        $userEmail = htmlspecialchars($_urow['email'] ?? '');
        // Fix session for subsequent page loads
        $_SESSION['first_name'] = htmlspecialchars_decode($firstName);
        $_SESSION['last_name'] = htmlspecialchars_decode($lastName);
        $_SESSION['email'] = $_urow['email'] ?? '';
    }
}

$userName = (!empty($firstName) || !empty($lastName)) ? trim("$firstName $lastName") : 'User';
$userInitial = !empty($firstName) ? strtoupper($firstName[0]) : 'U';

// Profile image: try session first (set during login), then try handler
$profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : null;
if (!$profileImage && isset($_SESSION['user_id'])) {
    $_pih = __DIR__ . '/../../includes/profile_image_handler.php';
    if (file_exists($_pih)) {
        @include_once $_pih;
        if (function_exists('getProfileImage')) {
            $profileImage = @getProfileImage($_SESSION['user_id']);
            if ($profileImage) {
                $_SESSION['profile_image'] = $profileImage;
            }
        }
    }
}
$profileImageSrc = $profileImage;
if ($profileImageSrc && !preg_match('/^(https?:|data:)/i', $profileImageSrc)) {
    $profileImageSrc = '../../' . ltrim($profileImageSrc, '/');
}

// Get pending report count for badge
$pendingCount = 0;
if (isset($conn) && !$conn->connect_error && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $r = @$conn->query("SELECT COUNT(*) as c FROM incidents WHERE user_id = $uid AND status='pending'");
    if ($r) $pendingCount = (int)mysqli_fetch_assoc($r)['c'];
}
?>
<aside class="dash-sidebar" id="dashSidebar">
  <a href="../../pro/landing.html" class="sidebar-logo" aria-label="Go to SheShield landing page">
    <div class="logo-mark">
      <svg viewBox="0 0 100 110" fill="none"><path d="M50 6 L88 22 L88 56 Q88 80 50 96 Q12 80 12 56 L12 22 Z" fill="none" stroke="#fff" stroke-width="4"/><circle cx="50" cy="40" r="10" stroke="#fff" stroke-width="2.5" fill="none"/><line x1="50" y1="50" x2="50" y2="68" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>
      <div class="live-badge"><div class="live-dot"></div></div>
    </div>
    <div>
      <div class="logo-name">SheShield</div>
      <div class="logo-sub">Safety Dashboard</div>
    </div>
  </a>

  <nav style="flex:1">
    <div class="nav-section-label">MAIN</div>
    <a href="dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-house"></i><span>Home</span>
    </a>
    <a href="report.php" class="nav-item <?= $activePage==='report'?'active':'' ?>">
      <i class="fa-solid fa-file-alt"></i><span>Reports</span>
      <?php if ($pendingCount > 0): ?><span class="nav-badge"><?=$pendingCount?></span><?php endif; ?>
    </a>
    <a href="analytics.php" class="nav-item <?= $activePage==='analytics'?'active':'' ?>">
      <i class="fa-solid fa-chart-bar"></i><span>Analytics</span>
    </a>
    <a href="map.php" class="nav-item <?= $activePage==='map'?'active':'' ?>">
      <i class="fa-solid fa-map-location-dot"></i><span>Map</span>
    </a>

    <div class="nav-section-label">SERVICES</div>
    <a href="safespace.php" class="nav-item <?= $activePage==='safespace'?'active':'' ?>">
      <i class="fa-solid fa-shield-heart"></i><span>Safe Space</span>
    </a>
    <a href="walkwithus.php" class="nav-item <?= $activePage==='walkwithus'?'active':'' ?>">
      <i class="fa-solid fa-person-walking"></i><span>Walk With Us</span>
    </a>
    <a href="templates.php" class="nav-item <?= $activePage==='templates'?'active':'' ?>">
      <i class="fa-solid fa-file-lines"></i><span>Templates</span>
    </a>

    <div class="nav-section-label">ACCOUNT</div>
    <a href="settings.php" class="nav-item <?= $activePage==='settings'?'active':'' ?>">
      <i class="fa-solid fa-gear"></i><span>Settings</span>
    </a>
    <a href="../../auth/logout.php" class="nav-item">
      <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
    </a>
  </nav>

  <button class="sidebar-sos" onclick="window.location.href='../../pro/report.html'">
    <div class="sos-dot"></div>
    <span>SOS Emergency</span>
  </button>

  <div class="sidebar-user">
    <div class="user-avatar">
      <?php if ($profileImageSrc): ?>
        <img src="<?= htmlspecialchars($profileImageSrc) ?>" alt="<?= $userName ?>">
      <?php else: ?>
        <?= $userInitial ?>
      <?php endif; ?>
    </div>
    <div style="flex:1;min-width:0">
      <div class="user-name"><?= $userName ?></div>
      <div class="user-status"><?= !empty($userEmail) ? $userEmail : 'Active member' ?></div>
    </div>
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
  </div>
</aside>

<button class="mobile-toggle" id="mobileToggle" onclick="document.getElementById('dashSidebar').classList.toggle('open')">
  <i class="fa-solid fa-bars"></i>
</button>
