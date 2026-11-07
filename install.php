<?php
/** One-time installer: imports database.sql and creates the first admin. DELETE THIS FILE AFTER INSTALLING. */
require_once __DIR__ . '/includes/init.php';

$step = 'connect'; $errors = []; $notice = ''; $dbError = '';

try {
    db();
    $step = 'tables';
} catch (Throwable $t) {
    $dbError = 'Could not connect to the database. Check DB_HOST, DB_NAME, DB_USER and DB_PASS in config/database.php.';
}

$adminCount = 0;
if ($step === 'tables') {
    try {
        $adminCount = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        $step = 'admin';
    } catch (Throwable $t) {
        $step = 'tables';
    }
}

if ($step !== 'connect' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';

    if ($action === 'import' && $step === 'tables') {
        $file = ROOT_DIR . '/database.sql';
        if (!is_file($file)) {
            $errors[] = 'database.sql was not found next to install.php.';
        } else {
            try {
                $lines = array_filter(preg_split('/\R/', (string)file_get_contents($file)), static function ($l) {
                    return strpos(ltrim($l), '--') !== 0;
                });
                foreach (preg_split('/;\s*[\r\n]+/', implode("\n", $lines) . "\n") as $stmt) {
                    $stmt = trim($stmt);
                    if ($stmt !== '') {
                        db()->exec($stmt);
                    }
                }
                redirect('install.php?imported=1');
            } catch (Throwable $t) {
                error_log('DEVIL EX install: ' . $t->getMessage());
                $errors[] = 'Import failed. Import database.sql manually with phpMyAdmin, then reload this page.';
            }
        }
    }

    if ($action === 'admin' && $step === 'admin' && $adminCount === 0) {
        $user  = clean_str($_POST['username'] ?? '', 50);
        $email = clean_str($_POST['email'] ?? '', 120);
        $name  = clean_str($_POST['display_name'] ?? '', 80);
        $pass  = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password2'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $user)) { $errors[] = 'Username must be 3-50 characters: letters, numbers, dot, dash, underscore.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email address.'; }
        if ($name === '') { $name = $user; }
        if (strlen($pass) < 10) { $errors[] = 'Password must be at least 10 characters.'; }
        if ($pass !== $pass2) { $errors[] = 'Passwords do not match.'; }
        if (!$errors) {
            try {
                db()->prepare('INSERT INTO admins (username, email, display_name, password_hash) VALUES (?, ?, ?, ?)')
                    ->execute([$user, $email, $name, password_hash($pass, PASSWORD_DEFAULT)]);
                set_setting('installed', '1');
                redirect('install.php?done=1');
            } catch (Throwable $t) {
                error_log('DEVIL EX install: ' . $t->getMessage());
                $errors[] = 'Could not create the admin account.';
            }
        }
    }
}

$layout = 'bare'; $pageTitle = 'Installer'; $noindex = true;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card glass" style="width:min(520px,100%)">
    <a class="brand" href="index.php"><img src="assets/images/logo.svg" alt="" width="44" height="44"><span><?= e(APP_NAME) ?> Installer</span></a>
    <p class="center muted">Set up your update control system in two steps.</p>

    <?php if ($errors): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><div><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div></div><?php endif; ?>
    <?php if (!empty($_GET['imported'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Database tables created.</div><?php endif; ?>

    <?php if (!empty($_GET['done'])): ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Installation complete. Admin account created.</div>
      <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Delete <b>install.php</b> and <b>database.sql</b> from your server now.</div>
      <a class="btn btn-primary btn-block" href="login.php">Go to admin login</a>

    <?php elseif ($step === 'connect'): ?>
      <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($dbError) ?></div>
      <p class="small muted">Open <code>config/database.php</code>, enter the MySQL host, database name, user and password from your hosting panel, upload it, then reload this page.</p>

    <?php elseif ($step === 'tables'): ?>
      <h3>Step 1 · Create database tables</h3>
      <p class="muted small">Connected to the database. Click below to import <code>database.sql</code> automatically (or import it in phpMyAdmin and reload).</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="import">
        <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-database"></i> Create tables</button></form>

    <?php elseif ($adminCount > 0): ?>
      <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> Already installed. An admin account exists.</div>
      <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Delete <b>install.php</b> and <b>database.sql</b> from your server.</div>
      <a class="btn btn-primary btn-block" href="login.php">Go to admin login</a>

    <?php else: ?>
      <h3>Step 2 · Create the admin account</h3>
      <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="admin">
        <div class="field"><label>Username</label><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="field"><label>Display name</label><input type="text" name="display_name" value="<?= e($_POST['display_name'] ?? '') ?>"></div>
        <div class="grid-2">
          <div class="field"><label>Password</label><input type="password" name="password" required minlength="10" autocomplete="new-password"></div>
          <div class="field"><label>Confirm</label><input type="password" name="password2" required minlength="10" autocomplete="new-password"></div>
        </div>
        <p class="small muted">Use at least 10 characters. The password is stored hashed with <code>password_hash()</code>.</p>
        <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-user-shield"></i> Create admin</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
