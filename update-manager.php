<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Update Dialog Manager'; $active = 'manager';

$apps = db()->query('SELECT id, name, package_name, app_id, is_active FROM applications ORDER BY name')->fetchAll();
$selId = (int)($_GET['app'] ?? $_POST['app_pk'] ?? 0);
if ($selId === 0 && $apps) {
    $selId = (int)$apps[0]['id'];
}
$app = $selId > 0 ? app_by_pk($selId) : null;
if (!$app && $apps) {
    $selId = (int)$apps[0]['id'];
    $app = app_by_pk($selId);
}
$errors = [];
$cfg = $app ? get_config($selId, $app) : [];

if ($app && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    [$clean, $errors] = validate_config($_POST);
    if (!$errors) {
        try {
            save_update_config($selId, $clean, $admin);
            flash('success', 'Update configuration saved. The API now serves these settings.');
            redirect('update-manager.php?app=' . $selId);
        } catch (Throwable $t) {
            error_log('DEVIL EX save config: ' . $t->getMessage());
            $errors[] = 'Could not save the configuration. Please try again.';
        }
    }
    $cfg = array_merge($cfg, $clean);
}
require __DIR__ . '/includes/header.php';
?>
<?php if (!$apps): ?>
  <div class="card center"><p class="muted">Add an application before configuring update dialogs.</p><a class="btn btn-primary" href="add-application.php"><i class="fa-solid fa-plus"></i> Add application</a></div>
<?php else: ?>
<div class="card">
  <form method="get" class="actions">
    <label for="appsel" class="label" style="margin:0">Application</label>
    <select id="appsel" name="app" data-app-switch style="max-width:340px">
      <?php foreach ($apps as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $selId ? 'selected' : '' ?>><?= e($a['name']) ?> (<?= e($a['package_name']) ?>)</option><?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-sm" type="submit">Load</button></noscript>
    <?= $app && !$app['is_active'] ? badge('Application inactive', 'red') : '' ?>
  </form>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="split">
<form method="post" id="cfg-form" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="app_pk" value="<?= $selId ?>">
  <div class="card">
    <h2><i class="fa-solid fa-bell"></i> Dialog behaviour</h2>
    <div class="switch-row"><div><b>Enable update dialog</b><span>OFF means the app never shows an update dialog</span></div>
      <label class="switch"><input type="checkbox" name="dialog_enabled" value="1" <?= !empty($cfg['dialog_enabled']) ? 'checked' : '' ?>><i></i></label></div>
    <div class="grid-2">
      <div class="field"><label for="update_type">Update type</label>
        <select id="update_type" name="update_type"><option value="optional" <?= $cfg['update_type'] === 'optional' ? 'selected' : '' ?>>Optional (Update Now / Later)</option><option value="mandatory" <?= $cfg['update_type'] === 'mandatory' ? 'selected' : '' ?>>Mandatory (cannot be dismissed)</option></select></div>
      <div class="field"><label for="theme">Dialog theme</label>
        <select id="theme" name="theme"><option value="light" <?= $cfg['theme'] === 'light' ? 'selected' : '' ?>>Light</option><option value="dark" <?= $cfg['theme'] === 'dark' ? 'selected' : '' ?>>Dark</option></select></div>
    </div>
    <div class="grid-3">
      <div class="field"><label for="latest_version">Latest version</label><input type="text" id="latest_version" name="latest_version" value="<?= e($cfg['latest_version']) ?>" required></div>
      <div class="field"><label for="latest_version_code">Latest version code</label><input type="number" id="latest_version_code" name="latest_version_code" min="1" value="<?= e($cfg['latest_version_code']) ?>" required></div>
      <div class="field"><label for="min_supported_version_code">Min supported code</label><input type="number" id="min_supported_version_code" name="min_supported_version_code" min="0" value="<?= e($cfg['min_supported_version_code']) ?>" required></div>
    </div>
    <div class="hint" style="margin:-8px 0 14px;font-size:.8rem;color:var(--muted)">Users below the minimum supported code always get a mandatory dialog.</div>
  </div>

  <div class="card">
    <h2><i class="fa-solid fa-message"></i> Dialog content</h2>
    <div class="field"><label for="title">Update title</label><input type="text" id="title" name="title" maxlength="150" value="<?= e($cfg['title']) ?>" required></div>
    <div class="field"><label for="message">Update message</label><textarea id="message" name="message" maxlength="500" required><?= e($cfg['message']) ?></textarea></div>
    <div class="field"><label for="description">Update description</label><textarea id="description" name="description" maxlength="2000"><?= e($cfg['description']) ?></textarea></div>
    <div class="field"><label for="changelog">Changelog</label><textarea id="changelog" name="changelog" maxlength="5000" placeholder="- Bug fixes&#10;- New features"><?= e($cfg['changelog']) ?></textarea></div>
    <div class="field"><label for="download_url">APK download URL</label><input type="url" id="download_url" name="download_url" maxlength="500" value="<?= e($cfg['download_url']) ?>" placeholder="https://example.com/app.apk"></div>
    <div class="grid-2">
      <div class="field"><label for="update_button_text">Update button text</label><input type="text" id="update_button_text" name="update_button_text" maxlength="40" value="<?= e($cfg['update_button_text']) ?>" required></div>
      <div class="field"><label for="cancel_button_text">Cancel button text</label><input type="text" id="cancel_button_text" name="cancel_button_text" maxlength="40" value="<?= e($cfg['cancel_button_text']) ?>" required></div>
    </div>
  </div>

  <div class="card">
    <h2><i class="fa-solid fa-screwdriver-wrench"></i> Maintenance mode</h2>
    <div class="switch-row"><div><b>Maintenance mode</b><span>Shows the message below instead of the app</span></div>
      <label class="switch"><input type="checkbox" name="maintenance_mode" value="1" <?= !empty($cfg['maintenance_mode']) ? 'checked' : '' ?>><i></i></label></div>
    <div class="field"><label for="maintenance_message">Maintenance message</label><textarea id="maintenance_message" name="maintenance_message" maxlength="500"><?= e($cfg['maintenance_message']) ?></textarea></div>
    <div class="actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save configuration</button></div>
  </div>
</form>

<div>
  <div class="card" style="position:sticky;top:90px">
    <h2><i class="fa-solid fa-eye"></i> Live preview</h2>
    <div class="preview-stage">
      <div id="dialog-preview" class="dialog-preview light">
        <h4 id="pv-title"></h4><p id="pv-message"></p><p id="pv-desc"></p>
        <div class="pv-btns"><span id="pv-cancel"></span><span id="pv-update"></span></div>
      </div>
    </div>
    <p class="small muted center mt" id="pv-state"></p>
    <div class="copy-line"><span class="tag">GET</span><span class="mono"><?= e(site_url('api/check_update.php?app_id=' . $app['app_id'] . '&version_code=1')) ?></span></div>
    <a class="btn btn-sm btn-block" target="_blank" rel="noopener" href="<?= e(site_url('api/check_update.php?app_id=' . $app['app_id'] . '&version_code=1')) ?>"><i class="fa-solid fa-flask"></i> Test API response</a>
  </div>
</div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
