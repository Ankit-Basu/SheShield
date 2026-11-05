<?php
require __DIR__ . '/_layout.php';

$me = ['first_name' => '', 'last_name' => '', 'full_name' => '', 'email' => '', 'phone' => '', 'phone_number' => '', 'emergency_contact_name' => '', 'emergency_contact_phone' => ''];
if (isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare('SELECT first_name, last_name, full_name, email, phone, phone_number, emergency_contact_name, emergency_contact_phone FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $me = array_merge($me, array_map(fn ($v) => (string)$v, $row));
            if ($me['first_name'] === '' && $me['full_name'] !== '') {
                [$me['first_name'], $me['last_name']] = array_pad(explode(' ', trim($me['full_name']), 2), 2, '');
            }
            if ($me['phone'] === '') $me['phone'] = $me['phone_number'];
        }
    }
}
$profileImage = $_SESSION['profile_image'] ?? null;
if ($profileImage && !preg_match('/^(https?:|data:)/i', $profileImage)) {
    $profileImage = '../../' . ltrim($profileImage, '/');
}
$display = trim($me['first_name'] . ' ' . $me['last_name']) ?: 'Member';

ss_page_open('settings', 'Account', 'Settings');
?>
<div class="grid g-main">
  <div class="stack">
    <form class="glass card-lg form-grid" id="profileForm" data-reveal>
      <h2 style="font-size:19px">Profile</h2>
      <div class="row wrap" style="gap:14px">
        <span class="avatar" id="avatarPreview" style="width:64px;height:64px;font-size:24px">
          <?php if ($profileImage): ?><img src="<?= e($profileImage) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($display, 0, 1))) ?><?php endif; ?>
        </span>
        <div style="flex:1;min-width:160px"><b style="display:block"><?= e($display) ?></b><span class="small muted"><?= e($me['email']) ?></span></div>
        <label class="btn btn-outline" style="cursor:pointer"><?= icon('camera') ?> Change photo<input class="sr-only" type="file" id="photoInput" accept="image/jpeg,image/png"></label>
      </div>
      <div class="form-row">
        <div class="field"><label class="label" for="pFirst">First name</label><input class="input" id="pFirst" value="<?= e($me['first_name']) ?>" autocomplete="given-name" required></div>
        <div class="field"><label class="label" for="pLast">Last name</label><input class="input" id="pLast" value="<?= e($me['last_name']) ?>" autocomplete="family-name"></div>
      </div>
      <div class="form-row">
        <div class="field"><label class="label" for="pEmail">Email</label><input class="input" id="pEmail" type="email" value="<?= e($me['email']) ?>" disabled><span class="hint">Your email is your sign-in, so it cannot be changed here.</span></div>
        <div class="field"><label class="label" for="pPhone">Phone</label><input class="input" id="pPhone" type="tel" value="<?= e($me['phone']) ?>" autocomplete="tel" placeholder="+91 XXXXX XXXXX"></div>
      </div>
      <hr class="separator">
      <div><h3 style="font-size:16px">Emergency contact</h3><p class="small muted">Who you would want told first. Kept on your account.</p></div>
      <div class="form-row">
        <div class="field"><label class="label" for="pCName">Name</label><input class="input" id="pCName" value="<?= e($me['emergency_contact_name']) ?>"></div>
        <div class="field"><label class="label" for="pCPhone">Phone</label><input class="input" id="pCPhone" type="tel" value="<?= e($me['emergency_contact_phone']) ?>"></div>
      </div>
      <button class="btn btn-primary" id="profileSave" type="submit" style="justify-self:start">Save changes</button>
    </form>

    <form class="glass card-lg form-grid" id="passwordForm" data-reveal>
      <h2 style="font-size:19px">Password</h2>
      <div class="field"><label class="label" for="pwCurrent">Current password</label><input class="input" type="password" id="pwCurrent" autocomplete="current-password" required></div>
      <div class="form-row">
        <div class="field"><label class="label" for="pwNew">New password</label><input class="input" type="password" id="pwNew" autocomplete="new-password" minlength="8" required></div>
        <div class="field"><label class="label" for="pwConfirm">Confirm new password</label><input class="input" type="password" id="pwConfirm" autocomplete="new-password" minlength="8" required></div>
      </div>
      <button class="btn btn-primary" id="passwordSave" type="submit" style="justify-self:start">Update password</button>
    </form>
  </div>

  <div class="stack">
    <div class="glass card-lg" data-reveal>
      <h2 style="font-size:19px;margin-bottom:4px">Appearance</h2>
      <p class="small muted" style="margin-bottom:14px">Remembered on this device.</p>
      <div class="row-between"><span class="small">Theme</span>
        <div class="theme-picker" role="group" aria-label="Theme">
          <button class="theme-swatch" data-set="dawn" aria-label="Sunset Horizon" title="Sunset Horizon"></button>
          <button class="theme-swatch" data-set="cosmic" aria-label="Cosmic Night" title="Cosmic Night"></button>
          <button class="theme-swatch" data-set="solar" aria-label="Solar Dusk" title="Solar Dusk"></button>
        </div>
      </div>
    </div>
    <div class="glass card-lg" data-reveal>
      <h2 style="font-size:19px;margin-bottom:6px">Close your account</h2>
      <p class="small muted" style="margin-bottom:14px">Account closure is not self-serve yet. Email us and we will handle it.</p>
      <a class="btn btn-outline-danger" href="mailto:help@sheshield.com?subject=Close%20my%20SheShield%20account"><?= icon('mail') ?> Request closure</a>
    </div>
  </div>
</div>
<?php ob_start(); ?>
<script>
const val = (id) => document.getElementById(id).value.trim();

document.getElementById('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('profileSave');
  SS.busy(btn, true, 'Saving');
  try {
    const d = await SS.post('../../api/user/update_profile.php', {
      first_name: val('pFirst'), last_name: val('pLast'), phone: val('pPhone'),
      emergency_contact_name: val('pCName'), emergency_contact_phone: val('pCPhone'),
    });
    showToast(d.message);
  } catch (err) { showToast(err.message, 'error'); }
  SS.busy(btn, false);
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  if (document.getElementById('pwNew').value !== document.getElementById('pwConfirm').value) {
    showToast('The new passwords do not match.', 'error');
    return;
  }
  const btn = document.getElementById('passwordSave');
  SS.busy(btn, true, 'Updating');
  try {
    const d = await SS.post('../../api/user/change_password.php', {
      current_password: document.getElementById('pwCurrent').value,
      new_password: document.getElementById('pwNew').value,
    });
    showToast(d.message);
    e.target.reset();
  } catch (err) { showToast(err.message, 'error'); }
  SS.busy(btn, false);
});

document.getElementById('photoInput').addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const form = new FormData();
  form.append('profile_image', file);
  try {
    const d = await SS.post('../../includes/handle_profile_update.php', form);
    document.getElementById('avatarPreview').innerHTML = `<img src="../../${d.image_path.replace(/^\//, '')}" alt="">`;
    showToast(d.message);
  } catch (err) { showToast(err.message, 'error'); }
  e.target.value = '';
});
</script>
<?php ss_page_close('settings', ob_get_clean()); ?>
