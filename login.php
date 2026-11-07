<?php
require_once __DIR__ . '/includes/init.php';

try {
    if ((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        redirect('install.php');
    }
} catch (Throwable $t) {
    redirect('install.php');
}
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $u = clean_str($_POST['username'] ?? '', 120);
    $p = (string)($_POST['password'] ?? '');
    if ($u === '' || $p === '' || strlen($p) > 1000) {
        $error = 'Enter your username and password.';
    } else {
        [$ok, $msg] = attempt_login($u, $p);
        if ($ok) {
            redirect('dashboard.php');
        }
        $error = $msg;
    }
}
$layout = 'bare'; $pageTitle = 'Admin Login'; $noindex = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card glass">
    <a class="brand" href="index.php"><img src="assets/images/logo.svg" alt="" width="44" height="44"><span><?= e(get_setting('site_name', APP_NAME)) ?></span></a>
    <p class="center muted">Sign in to the update control panel</p>
    <?php if (!empty($_GET['bye'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> You have been signed out.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <span><?= e($error) ?></span></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field"><label for="username">Username or email</label><input type="text" id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></div>
      <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
      <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
    </form>
    <p class="center small muted mt"><a href="index.php">&larr; Back to website</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
