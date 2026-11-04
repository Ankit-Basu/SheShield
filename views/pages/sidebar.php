<?php
// Shared sidebar, included by ss_page_open() where $activePage and $conn are in scope.
$firstName = trim((string)($_SESSION['first_name'] ?? ''));
$lastName = trim((string)($_SESSION['last_name'] ?? ''));
$userEmail = (string)($_SESSION['email'] ?? '');
$sessionUserId = (int)($_SESSION['user_id'] ?? 0);

// Fallback: if the session has no name, load it once from the database.
if ($firstName === '' && $sessionUserId > 0 && isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare('SELECT first_name, last_name, full_name, email FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $sessionUserId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $firstName = trim((string)($row['first_name'] ?? ''));
            $lastName = trim((string)($row['last_name'] ?? ''));
            if ($firstName === '' && !empty($row['full_name'])) {
                $parts = explode(' ', trim($row['full_name']), 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? $lastName;
            }
            $userEmail = (string)($row['email'] ?? '');
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $userEmail;
        }
    }
}

$displayName = trim("$firstName $lastName") ?: 'Member';
$initial = strtoupper(substr($firstName ?: 'M', 0, 1));

$profileImage = $_SESSION['profile_image'] ?? null;
if (!$profileImage && $sessionUserId > 0) {
    $handler = __DIR__ . '/../../includes/profile_image_handler.php';
    if (file_exists($handler)) {
        include_once $handler;
        if (function_exists('getProfileImage')) {
            $profileImage = @getProfileImage($sessionUserId);
            if ($profileImage) $_SESSION['profile_image'] = $profileImage;
        }
    }
}
if ($profileImage && !preg_match('/^(https?:|data:)/i', $profileImage)) {
    $profileImage = '../../' . ltrim($profileImage, '/');
}

$pendingCount = 0;
if ($sessionUserId > 0 && isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT COUNT(*) AS c FROM incidents WHERE user_id = $sessionUserId AND status = 'pending'");
    if ($r) $pendingCount = (int)$r->fetch_assoc()['c'];
}

$navGroups = [
    'Main' => [
        ['dashboard', 'Home', 'house'],
        ['report', 'Reports', 'file-text'],
        ['analytics', 'Analytics', 'chart-column'],
        ['map', 'Safety map', 'map'],
    ],
    'Services' => [
        ['safespace', 'Safe spaces', 'shield-check'],
        ['walkwithus', 'Walk with us', 'footprints'],
        ['templates', 'Templates', 'files'],
    ],
];
?>
<aside class="side glass" id="dashSidebar" aria-label="Main navigation">
  <div class="side-brand">
    <a href="../../pro/landing.html" class="brand" aria-label="SheShield home">
      <svg class="brand-mark" viewBox="0 0 100 110" fill="none" aria-hidden="true"><defs><linearGradient id="ssLogo" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--primary)"/><stop offset="1" stop-color="var(--accent)"/></linearGradient></defs><path d="M50 6 88 22v34q0 24-38 40Q12 80 12 56V22Z" fill="url(#ssLogo)"/><circle cx="50" cy="40" r="12" stroke="#1d121b" stroke-width="5"/><path d="M50 52v21M41 64h18" stroke="#1d121b" stroke-width="5" stroke-linecap="round"/></svg>
      SheShield
    </a>
  </div>
  <nav class="side-nav">
    <?php foreach ($navGroups as $label => $links): ?>
      <div class="side-label"><?= e($label) ?></div>
      <?php foreach ($links as [$slug, $text, $ico]): ?>
        <a href="<?= $slug ?>.php" class="side-link <?= $activePage === $slug ? 'active' : '' ?>" <?= $activePage === $slug ? 'aria-current="page"' : '' ?>>
          <?= icon($ico) ?><span><?= e($text) ?></span>
          <?php if ($slug === 'report' && $pendingCount > 0): ?><span class="side-count" title="Pending reports"><?= $pendingCount ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="side-label">Account</div>
    <a href="settings.php" class="side-link <?= $activePage === 'settings' ? 'active' : '' ?>"><?= icon('settings') ?><span>Settings</span></a>
    <a href="../../auth/logout.php" class="side-link"><?= icon('log-out') ?><span>Log out</span></a>
  </nav>
  <div class="side-foot">
    <a href="settings.php" class="side-user">
      <span class="avatar">
        <?php if ($profileImage): ?><img src="<?= e($profileImage) ?>" alt="" loading="lazy"><?php else: ?><?= e($initial) ?><?php endif; ?>
      </span>
      <span style="min-width:0">
        <b><?= e($displayName) ?></b>
        <small><?= e($userEmail ?: 'Member') ?></small>
      </span>
    </a>
  </div>
</aside>
