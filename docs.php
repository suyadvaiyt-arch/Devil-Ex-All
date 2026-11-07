<?php
require_once __DIR__ . '/includes/init.php';
$layout = 'public'; $pageTitle = 'Documentation';
$api = site_url('api/');
$kt  = @file_get_contents(__DIR__ . '/docs/UpdateChecker.kt') ?: '// docs/UpdateChecker.kt is missing from the server.';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap" style="padding:50px 0 10px;max-width:900px">
  <h1>Android Integration Guide</h1>
  <p class="muted">Connect any Android APK to <?= e(get_setting('site_name', APP_NAME)) ?> and show native update dialogs controlled from the admin panel.</p>

  <div class="card" id="connect">
    <h2><i class="fa-solid fa-plug"></i> 1. Connect your app</h2>
    <p>Add your application in the admin panel. It gets a unique 32-character <b>Application ID</b> (Manage Applications &rarr; Edit). Your app sends two values on every launch:</p>
    <ul>
      <li><code>app_id</code>: the Application ID</li>
      <li><code>version_code</code>: the installed <code>versionCode</code> from <code>build.gradle</code></li>
    </ul>
    <div class="copy-line"><span class="tag">GET</span><span class="mono"><?= e($api) ?>check_update.php?app_id=YOUR_APP_ID&amp;version_code=100</span></div>
  </div>

  <div class="card" id="endpoints">
    <h2><i class="fa-solid fa-list"></i> 2. Endpoints</h2>
    <div class="table-wrap"><table>
      <thead><tr><th>Endpoint</th><th>Method</th><th>Parameters</th><th>Purpose</th></tr></thead>
      <tbody>
        <tr><td class="mono">check_update.php</td><td>GET</td><td class="mono">app_id, version_code</td><td>Update / maintenance decision</td></tr>
        <tr><td class="mono">get_app_config.php</td><td>GET</td><td class="mono">app_id</td><td>Full stored configuration</td></tr>
        <tr><td class="mono">report_download.php</td><td>POST</td><td class="mono">app_id, version_code, target_version_code?</td><td>User tapped Update</td></tr>
        <tr><td class="mono">report_update.php</td><td>POST</td><td class="mono">app_id, from_version_code, to_version_code</td><td>Update completed</td></tr>
      </tbody>
    </table></div>
    <p class="mt"><b>HTTP status codes:</b> <code>200</code> OK · <code>400</code> missing or invalid parameter · <code>403</code> application disabled · <code>404</code> unknown Application ID · <code>405</code> wrong method · <code>429</code> rate limit exceeded · <code>500</code> server error. Errors always return JSON: <code>{"success":false,"error":"..."}</code></p>
  </div>

  <div class="card" id="response">
    <h2><i class="fa-solid fa-code"></i> 3. Parse the JSON response</h2>
<pre class="code">{
  "success": true,
  "update_available": true,
  "mandatory": false,
  "app_name": "DEVIL EX APP",
  "latest_version": "2.0.0",
  "latest_version_code": 200,
  "min_supported_version_code": 100,
  "title": "New Update Available",
  "message": "A new version is available.",
  "description": "Enjoy the latest features and improvements.",
  "changelog": "- Faster startup",
  "download_url": "https://example.com/app.apk",
  "update_button": "Update Now",
  "cancel_button": "Later",
  "theme": "light",
  "maintenance_mode": false,
  "maintenance_message": "We are under maintenance."
}</pre>
    <ul>
      <li><b>maintenance_mode = true</b> &rarr; show the maintenance dialog first. It cannot be dismissed.</li>
      <li><b>update_available = false</b> &rarr; show nothing.</li>
      <li><b>update_available = true, mandatory = false</b> &rarr; optional dialog with Update Now and Later.</li>
      <li><b>update_available = true, mandatory = true</b> &rarr; dialog with no cancel button; back and outside taps disabled. An update is also mandatory when the installed version code is below <code>min_supported_version_code</code>.</li>
    </ul>
  </div>

  <div class="card" id="kotlin">
    <h2><i class="fa-brands fa-android"></i> 4. Kotlin example</h2>
    <p>Copy this file into your project (also included as <code>docs/UpdateChecker.kt</code>), set <code>BASE_URL</code> and <code>APP_ID</code>, then call <code>DevilExUpdater.check(this)</code> in your main activity. It handles optional updates, mandatory updates, maintenance mode and opens the APK download URL in the browser.</p>
    <pre class="code"><?= e($kt) ?></pre>
  </div>

  <div class="card">
    <h2><i class="fa-solid fa-circle-check"></i> Good practice</h2>
    <ul>
      <li>Never block the app if the request fails: ignore network errors.</li>
      <li>Use HTTPS for both the API and the APK download link.</li>
      <li>Increase <code>versionCode</code> in every release, and set the same number as <i>Latest Version Code</i> in the Update Dialog Manager.</li>
      <li>The API is plain HTTP and JSON, so it works from any framework, not just native Android.</li>
    </ul>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
