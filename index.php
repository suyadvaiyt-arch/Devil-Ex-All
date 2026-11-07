<?php
require_once __DIR__ . '/includes/init.php';
$layout = 'public'; $pageTitle = 'Online APK Update Control';

$dbOk = true; $activeApps = 0; $requests24 = 0;
try {
    $activeApps = (int)db()->query('SELECT COUNT(*) FROM applications WHERE is_active = 1')->fetchColumn();
    $requests24 = (int)db()->query('SELECT COUNT(*) FROM api_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)')->fetchColumn();
} catch (Throwable $t) {
    $dbOk = false;
}
require __DIR__ . '/includes/header.php';
?>
<section class="hero wrap">
  <span class="pill"><i class="fa-solid fa-bolt"></i> Remote update dialogs for Android</span>
  <h1>Control your APK update dialogs <em>from the cloud</em></h1>
  <p><?= e(get_setting('site_name', APP_NAME)) ?> lets you publish optional, mandatory and maintenance notices to every app you ship, without releasing a new build.</p>
  <div class="actions">
    <a class="btn btn-primary" href="docs.php"><i class="fa-solid fa-book"></i> Integration guide</a>
    <a class="btn" href="status.php"><i class="fa-solid fa-signal"></i> View update status</a>
  </div>
</section>

<section class="section" id="about"><div class="wrap">
  <h2>What is <?= e(get_setting('site_name', APP_NAME)) ?>?</h2>
  <p class="sub">A lightweight PHP and MySQL platform. You manage apps in an admin panel; your Android app reads one JSON endpoint and shows a native dialog.</p>
</div></section>

<section class="section" id="features"><div class="wrap">
  <h2>Features</h2><p class="sub">Everything needed to run updates for many apps from one place.</p>
  <div class="feat-grid">
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-sliders"></i></div><h3>Remote dialog control</h3><p>Switch the dialog on or off, edit titles, messages, buttons, changelog and theme instantly.</p></div>
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-lock"></i></div><h3>Mandatory updates</h3><p>Force users onto a new build. The dialog cannot be dismissed with cancel or back.</p></div>
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-screwdriver-wrench"></i></div><h3>Maintenance mode</h3><p>Show your own maintenance message to every user of an app with one switch.</p></div>
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-layer-group"></i></div><h3>Multi-app</h3><p>Each application has its own unique ID and its own independent configuration.</p></div>
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-clock-rotate-left"></i></div><h3>Full history</h3><p>Every change is stored with a snapshot, so you can review and restore earlier settings.</p></div>
    <div class="feat glass"><div class="ic"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure by design</h3><p>Prepared statements, CSRF tokens, hashed passwords, rate limiting and request logging.</p></div>
  </div>
</div></section>

<section class="section" id="how"><div class="wrap">
  <h2>How it works</h2><p class="sub">Four steps from zero to a live update dialog.</p>
  <div class="steps">
    <div class="step glass"><h3>Add your app</h3><p>Register the package name and get a unique Application ID.</p></div>
    <div class="step glass"><h3>Configure the dialog</h3><p>Set version code, type, texts and the APK download link.</p></div>
    <div class="step glass"><h3>App calls the API</h3><p>Your APK sends its ID and version code to <code>check_update.php</code>.</p></div>
    <div class="step glass"><h3>Native dialog shows</h3><p>The app reads the JSON and displays the update or maintenance dialog.</p></div>
  </div>
</div></section>

<section class="section" id="api"><div class="wrap">
  <h2>API integration</h2><p class="sub">One request, one JSON answer.</p>
  <div class="card">
    <div class="copy-line"><span class="tag">GET</span><span class="mono"><?= e(site_url('api/check_update.php?app_id=YOUR_APP_ID&version_code=100')) ?></span></div>
<pre class="code">{
  "success": true,
  "update_available": true,
  "mandatory": false,
  "latest_version": "2.0.0",
  "latest_version_code": 200,
  "title": "New Update Available",
  "download_url": "https://example.com/app.apk",
  "update_button": "Update Now",
  "cancel_button": "Later",
  "theme": "light",
  "maintenance_mode": false
}</pre>
    <p class="muted small">Other endpoints: <code>get_app_config.php</code>, <code>report_download.php</code>, <code>report_update.php</code>.</p>
    <a class="btn btn-primary btn-sm" href="docs.php"><i class="fa-brands fa-android"></i> Read the Android guide</a>
  </div>
</div></section>

<section class="section" id="docs"><div class="wrap">
  <h2>Documentation</h2><p class="sub">Step-by-step guides for developers.</p>
  <div class="feat-grid">
    <a class="feat glass" href="docs.php#connect" style="text-decoration:none;color:inherit"><div class="ic"><i class="fa-brands fa-android"></i></div><h3>Android integration</h3><p>Connect your APK, parse the JSON and show native dialogs in Kotlin.</p></a>
    <a class="feat glass" href="docs.php#endpoints" style="text-decoration:none;color:inherit"><div class="ic"><i class="fa-solid fa-plug"></i></div><h3>API reference</h3><p>Parameters, responses and HTTP status codes for every endpoint.</p></a>
    <a class="feat glass" href="status.php" style="text-decoration:none;color:inherit"><div class="ic"><i class="fa-solid fa-signal"></i></div><h3>Update status</h3><p>See the live update information configured for each application.</p></a>
  </div>
</div></section>

<section class="section" id="status"><div class="wrap">
  <h2>System status</h2>
  <div class="card" style="max-width:560px;margin:0 auto">
    <div class="status-row"><span class="dot <?= $dbOk ? '' : 'bad' ?>"></span><b>Database</b><span class="muted" style="margin-left:auto"><?= $dbOk ? 'Operational' : 'Unavailable' ?></span></div>
    <div class="status-row"><span class="dot <?= $dbOk ? '' : 'bad' ?>"></span><b>Update API</b><span class="muted" style="margin-left:auto"><?= $dbOk ? 'Operational' : 'Unavailable' ?></span></div>
    <div class="status-row"><span class="dot"></span><b>Active applications</b><span class="muted" style="margin-left:auto"><?= $activeApps ?></span></div>
    <div class="status-row"><span class="dot"></span><b>API requests (24h)</b><span class="muted" style="margin-left:auto"><?= $requests24 ?></span></div>
  </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
