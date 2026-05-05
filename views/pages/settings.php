<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
require_once __DIR__ . '/../../app/models/mysqli_db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$activePage = 'settings';
$today = strtoupper(date('l, F j'));

$firstName = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : '';
$lastName = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : '';
$profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : null;
if (!$profileImage && $user_id > 0) {
    $profileHandler = __DIR__ . '/../../includes/profile_image_handler.php';
    if (file_exists($profileHandler)) {
        require_once $profileHandler;
        if (function_exists('getProfileImage')) {
            $profileImage = getProfileImage($user_id);
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

// Fallback: if session is empty but user_id exists, load from DB
if (empty($firstName) && $user_id > 0 && isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT first_name, last_name, full_name, email FROM users WHERE id = $user_id LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $firstName = htmlspecialchars($row['first_name'] ?? '');
        $lastName = htmlspecialchars($row['last_name'] ?? '');
        if ($firstName === '' && !empty($row['full_name'])) {
            $parts = explode(' ', trim($row['full_name']), 2);
            $firstName = htmlspecialchars($parts[0] ?? '');
            $lastName = htmlspecialchars($parts[1] ?? $lastName);
        }
        $email = htmlspecialchars($row['email'] ?? '');
        // Also fix session for future page loads
        $_SESSION['first_name'] = $row['first_name'] ?? '';
        $_SESSION['last_name'] = $row['last_name'] ?? '';
        $_SESSION['email'] = $row['email'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — SheShield</title>
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
        <div class="topbar-date"><?= $today ?> — SETTINGS</div>
        <h1 class="topbar-title">Account <span class="gradient-text">Settings</span></h1>
      </div>
    </div>
    <div class="dash-inner">
      <div style="max-width:700px">
        <!-- Profile -->
        <div class="glass-card mb-24">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Profile Information</h3>
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
            <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,rgba(233,30,140,0.4),rgba(124,58,237,0.4));border:2px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;overflow:hidden">
              <?php if ($profileImageSrc): ?>
                <img src="<?= htmlspecialchars($profileImageSrc) ?>" alt="<?= "$firstName $lastName" ?: 'User' ?>" style="width:100%;height:100%;object-fit:cover">
              <?php else: ?>
                <?= !empty($firstName) ? strtoupper($firstName[0]) : 'U' ?>
              <?php endif; ?>
            </div>
            <div>
              <div style="font-size:16px;font-weight:600"><?= "$firstName $lastName" ?: 'User' ?></div>
              <div style="font-size:12px;color:rgba(255,255,255,0.4)"><?= $email ?: 'Not set' ?></div>
            </div>
            <button class="btn-secondary" style="margin-left:auto">Change Photo</button>
          </div>
          <form style="display:flex;flex-direction:column;gap:14px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">First Name</label><input type="text" value="<?= $firstName ?>" class="premium-input"></div>
              <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Last Name</label><input type="text" value="<?= $lastName ?>" class="premium-input"></div>
            </div>
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Email</label><input type="email" value="<?= $email ?>" class="premium-input"></div>
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Phone</label><input type="tel" placeholder="+91 XXXXX XXXXX" class="premium-input"></div>
            <button type="button" class="btn-primary" style="padding:12px;border-radius:12px;width:fit-content" onclick="showToast('Profile saved successfully!','success')">Save Changes</button>
          </form>
        </div>

        <!-- Security -->
        <div class="glass-card mb-24">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Security</h3>
          <form style="display:flex;flex-direction:column;gap:14px">
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Current Password</label><input type="password" class="premium-input" placeholder="••••••••"></div>
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">New Password</label><input type="password" class="premium-input" placeholder="••••••••"></div>
            <div><label style="font-size:12px;color:rgba(255,255,255,0.5);margin-bottom:6px;display:block">Confirm Password</label><input type="password" class="premium-input" placeholder="••••••••"></div>
            <button type="button" class="btn-primary" style="padding:12px;border-radius:12px;width:fit-content" onclick="showToast('Password updated!','success')">Update Password</button>
          </form>
        </div>

        <!-- Notifications -->
        <div class="glass-card mb-24">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Notifications</h3>
          <?php
          $notifs = [
            ['Emergency Alerts','Get notified about emergencies in your area',true],
            ['Walk Updates','Updates about your walk requests and walkers',true],
            ['Report Status','When your incident report status changes',true],
            ['Email Notifications','Receive email notifications',false],
            ['SMS Alerts','Receive SMS alerts for emergencies',false],
          ];
          foreach ($notifs as $n): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.06)">
            <div>
              <div style="font-size:13px;font-weight:500"><?= $n[0] ?></div>
              <div style="font-size:11px;color:rgba(255,255,255,0.4)"><?= $n[1] ?></div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer">
              <input type="checkbox" <?= $n[2]?'checked':'' ?> style="opacity:0;width:0;height:0">
              <span style="position:absolute;inset:0;background:<?= $n[2]?'linear-gradient(135deg,#e91e8c,#7c3aed)':'rgba(255,255,255,0.1)' ?>;border-radius:24px;transition:0.3s"></span>
              <span style="position:absolute;left:<?= $n[2]?'22px':'2px' ?>;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:0.3s"></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Danger Zone -->
        <div class="glass-card" style="border-color:rgba(239,68,68,0.2)">
          <h3 style="font-size:16px;font-weight:700;color:#f87171;margin-bottom:8px">Danger Zone</h3>
          <p style="font-size:12px;color:rgba(255,255,255,0.4);margin-bottom:16px">Irreversible actions. Proceed with caution.</p>
          <div style="display:flex;gap:10px">
            <button class="btn-secondary" style="border-color:rgba(239,68,68,0.3);color:#f87171">Deactivate Account</button>
            <button style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:9px 14px;color:#f87171;font-size:12px;cursor:pointer">Delete Account</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/toast.php'; ?>
</body>
</html>
