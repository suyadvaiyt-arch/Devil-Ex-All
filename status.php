<?php
require_once __DIR__ . '/includes/init.php';
$layout = 'public'; $pageTitle = 'Update Status';
$apps = []; $error = null; $enabled = get_setting('public_status_enabled', '1') === '1';
if ($enabled) {
    try {
        $apps = db()->query('SELECT a.name, a.package_name, a.icon_path, a.latest_version AS app_latest,
                c.dialog_enabled, c.update_type, c.latest_version, c.latest_version_code, c.maintenance_mode, c.updated_at
            FROM applications a LEFT JOIN update_configs c ON c.application_id = a.id
            WHERE a.is_active = 1 ORDER BY a.name')->fetchAll();
    } catch (Throwable $t) {
        $error = 'Status is temporarily unavailable.';
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="wrap" style="padding:50px 0 10px">
  <h1 class="center">Update Status</h1>
  <p class="center muted">Currently configured update information for each application.</p>
  <?php if (!$enabled): ?>
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> The public status page is turned off.</div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
  <?php elseif (!$apps): ?>
    <div class="card center muted">No applications are listed yet.</div>
  <?php else: ?>
  <div class="app-status">
    <?php foreach ($apps as $a): ?>
      <div class="card">
        <div class="app-cell" style="margin-bottom:14px">
          <img src="<?= e(icon_url($a['icon_path'])) ?>" alt="">
          <div><b><?= e($a['name']) ?></b><div class="muted small mono"><?= e($a['package_name']) ?></div></div>
        </div>
        <div class="actions" style="margin-bottom:12px">
          <?php if ((int)$a['maintenance_mode'] === 1): ?><?= badge('Maintenance', 'amber') ?>
          <?php elseif ((int)$a['dialog_enabled'] === 1): ?><?= badge(ucfirst((string)$a['update_type']) . ' update', $a['update_type'] === 'mandatory' ? 'red' : 'blue') ?>
          <?php else: ?><?= badge('Up to date', 'green') ?><?php endif; ?>
        </div>
        <dl class="kv">
          <dt>Latest version</dt><dd><?= e($a['latest_version'] ?? $a['app_latest']) ?><?= $a['latest_version_code'] ? ' (code ' . (int)$a['latest_version_code'] . ')' : '' ?></dd>
          <dt>Update dialog</dt><dd><?= (int)$a['dialog_enabled'] === 1 ? 'Enabled' : 'Off' ?></dd>
          <dt>Last changed</dt><dd><?= e(fmt_date($a['updated_at'])) ?></dd>
        </dl>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
