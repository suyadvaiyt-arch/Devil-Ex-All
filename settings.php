<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'System Settings'; $active = 'settings';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $range = static function (string $k, int $min, int $max, string $label) use (&$errors): int {
        $v = $_POST[$k] ?? '';
        if (!is_numeric($v) || (int)$v < $min || (int)$v > $max) {
            $errors[] = "$label must be between $min and $max.";
            return $min;
        }
        return (int)$v;
    };
    $vals = [
        'site_name'             => clean_str($_POST['site_name'] ?? '', 60),
        'site_tagline'          => clean_str($_POST['site_tagline'] ?? '', 120),
        'api_rate_limit'        => (string)$range('api_rate_limit', 1, 5000, 'API requests per window'),
        'api_rate_window_sec'   => (string)$range('api_rate_window_sec', 10, 3600, 'Rate-limit window (seconds)'),
        'login_max_attempts'    => (string)$range('login_max_attempts', 1, 20, 'Max login attempts'),
        'login_lockout_minutes' => (string)$range('login_lockout_minutes', 1, 1440, 'Lockout minutes'),
        'log_retention_days'    => (string)$range('log_retention_days', 1, 3650, 'Log retention days'),
        'api_logging_enabled'   => !empty($_POST['api_logging_enabled']) ? '1' : '0',
        'public_status_enabled' => !empty($_POST['public_status_enabled']) ? '1' : '0',
        'trust_cf_ip'           => !empty($_POST['trust_cf_ip']) ? '1' : '0',
    ];
    if ($vals['site_name'] === '') {
        $errors[] = 'Site name is required.';
    }
    if (!$errors) {
        foreach ($vals as $k => $v) {
            set_setting($k, $v);
        }
        flash('success', 'Settings saved.');
        redirect('settings.php');
    }
}
$g = static fn(string $k, string $d = '') => $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$k]) ? (string)$_POST[$k] : get_setting($k, $d);
$chk = static fn(string $k, string $d) => $_SERVER['REQUEST_METHOD'] === 'POST' ? !empty($_POST[$k]) : get_setting($k, $d) === '1';
require __DIR__ . '/includes/header.php';
?>
<form method="post" autocomplete="off">
  <?= csrf_field() ?>
  <?php if ($errors): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="card"><h2><i class="fa-solid fa-palette"></i> Branding</h2>
    <div class="grid-2">
      <div class="field"><label for="site_name">Site name</label><input type="text" id="site_name" name="site_name" maxlength="60" value="<?= e($g('site_name', APP_NAME)) ?>" required></div>
      <div class="field"><label for="site_tagline">Tagline</label><input type="text" id="site_tagline" name="site_tagline" maxlength="120" value="<?= e($g('site_tagline', APP_TAGLINE)) ?>"></div>
    </div>
    <div class="switch-row"><div><b>Public status page</b><span>Show configured update info at status.php</span></div><label class="switch"><input type="checkbox" name="public_status_enabled" value="1" <?= $chk('public_status_enabled', '1') ? 'checked' : '' ?>><i></i></label></div>
  </div>
  <div class="card"><h2><i class="fa-solid fa-gauge"></i> API protection</h2>
    <div class="grid-2">
      <div class="field"><label for="api_rate_limit">Requests allowed per window (per IP)</label><input type="number" id="api_rate_limit" name="api_rate_limit" min="1" max="5000" value="<?= e($g('api_rate_limit', '60')) ?>"></div>
      <div class="field"><label for="api_rate_window_sec">Window length (seconds)</label><input type="number" id="api_rate_window_sec" name="api_rate_window_sec" min="10" max="3600" value="<?= e($g('api_rate_window_sec', '60')) ?>"></div>
    </div>
    <div class="switch-row"><div><b>Log API requests</b><span>Stores endpoint, app, version code, status and IP</span></div><label class="switch"><input type="checkbox" name="api_logging_enabled" value="1" <?= $chk('api_logging_enabled', '1') ? 'checked' : '' ?>><i></i></label></div>
    <div class="field"><label for="log_retention_days">Default log retention (days)</label><input type="number" id="log_retention_days" name="log_retention_days" min="1" max="3650" value="<?= e($g('log_retention_days', '30')) ?>"><div class="hint">Used as the suggested value when deleting old logs in API Management.</div></div>
    <div class="switch-row"><div><b>Trust Cloudflare IP header</b><span>Turn on only if every visitor really passes through Cloudflare; otherwise IPs can be spoofed</span></div><label class="switch"><input type="checkbox" name="trust_cf_ip" value="1" <?= $chk('trust_cf_ip', '0') ? 'checked' : '' ?>><i></i></label></div>
  </div>
  <div class="card"><h2><i class="fa-solid fa-user-lock"></i> Login protection</h2>
    <div class="grid-2">
      <div class="field"><label for="login_max_attempts">Failed attempts before lockout</label><input type="number" id="login_max_attempts" name="login_max_attempts" min="1" max="20" value="<?= e($g('login_max_attempts', '5')) ?>"></div>
      <div class="field"><label for="login_lockout_minutes">Lockout duration (minutes)</label><input type="number" id="login_lockout_minutes" name="login_lockout_minutes" min="1" max="1440" value="<?= e($g('login_lockout_minutes', '15')) ?>"></div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
  </div>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
