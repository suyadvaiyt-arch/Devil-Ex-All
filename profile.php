<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Admin Profile'; $active = 'profile';
$errP = []; $errW = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (($_POST['action'] ?? '') === 'profile') {
        $name  = clean_str($_POST['display_name'] ?? '', 80);
        $email = clean_str($_POST['email'] ?? '', 120);
        if ($name === '') { $errP[] = 'Display name is required.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errP[] = 'Enter a valid email address.'; }
        if (!$errP) {
            $st = db()->prepare('SELECT 1 FROM admins WHERE email = ? AND id <> ?');
            $st->execute([$email, $admin['id']]);
            if ($st->fetchColumn()) { $errP[] = 'That email is already used by another admin.'; }
        }
        if (!$errP) {
            db()->prepare('UPDATE admins SET display_name = ?, email = ? WHERE id = ?')->execute([$name, $email, $admin['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? '');
        $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $st->execute([$admin['id']]);
        $hash = (string)$st->fetchColumn();
        if (!password_verify($cur, $hash)) { $errW[] = 'Current password is incorrect.'; }
        if (strlen($new) < 10) { $errW[] = 'New password must be at least 10 characters.'; }
        if ($new !== $new2) { $errW[] = 'New passwords do not match.'; }
        if (!$errW) {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}
$st = db()->prepare('SELECT username, email, display_name, last_login_at, last_login_ip, created_at FROM admins WHERE id = ?');
$st->execute([$admin['id']]);
$me = $st->fetch();
require __DIR__ . '/includes/header.php';
?>
<div class="split">
  <div class="card">
    <h2><i class="fa-solid fa-user"></i> Profile</h2>
    <?php if ($errP): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errP as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label>Username</label><input type="text" value="<?= e($me['username']) ?>" disabled></div>
      <div class="field"><label for="display_name">Display name</label><input type="text" id="display_name" name="display_name" maxlength="80" value="<?= e($_POST['display_name'] ?? $me['display_name']) ?>" required></div>
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="120" value="<?= e($_POST['email'] ?? $me['email']) ?>" required></div>
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save profile</button>
    </form>
    <p class="muted small mt">Last sign-in: <?= e(fmt_date($me['last_login_at'])) ?> from <?= e($me['last_login_ip'] ?? '—') ?></p>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-key"></i> Change password</h2>
    <?php if ($errW): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errW as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="field"><label for="current">Current password</label><input type="password" id="current" name="current" required autocomplete="current-password"></div>
      <div class="field"><label for="new">New password</label><input type="password" id="new" name="new" minlength="10" required autocomplete="new-password"></div>
      <div class="field"><label for="new2">Confirm new password</label><input type="password" id="new2" name="new2" minlength="10" required autocomplete="new-password"></div>
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-lock"></i> Update password</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
