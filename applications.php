<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Manage Applications'; $active = 'apps';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id  = (int)($_POST['id'] ?? 0);
    $app = $id > 0 ? app_by_pk($id) : null;
    if (!$app) {
        flash('danger', 'Application not found.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        db()->prepare('UPDATE applications SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        flash('success', $app['name'] . ($app['is_active'] ? ' deactivated.' : ' activated.'));
    } elseif (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
        delete_icon($app['icon_path']);
        flash('success', $app['name'] . ' deleted with its configuration and history.');
    }
    redirect('applications.php');
}

$apps = db()->query('SELECT a.*, c.dialog_enabled, c.update_type, c.maintenance_mode, c.latest_version_code
                     FROM applications a LEFT JOIN update_configs c ON c.application_id = a.id ORDER BY a.created_at DESC')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-head"><h2><i class="fa-solid fa-mobile-screen"></i> Applications (<?= count($apps) ?>)</h2><a class="btn btn-primary btn-sm" href="add-application.php"><i class="fa-solid fa-plus"></i> Add application</a></div>
  <?php if (!$apps): ?><p class="muted">No applications yet.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Application</th><th>Versions</th><th>Update dialog</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($apps as $a): ?>
      <tr>
        <td><div class="app-cell"><img src="<?= e(icon_url($a['icon_path'])) ?>" alt=""><div><b><?= e($a['name']) ?></b><div class="muted small mono"><?= e($a['package_name']) ?></div></div></div></td>
        <td>Latest <b><?= e($a['latest_version']) ?></b><div class="muted small">Min <?= e($a['min_supported_version']) ?></div></td>
        <td><?php if ((int)$a['maintenance_mode'] === 1): ?><?= badge('Maintenance', 'amber') ?>
            <?php elseif ((int)$a['dialog_enabled'] === 1): ?><?= badge(ucfirst($a['update_type']), $a['update_type'] === 'mandatory' ? 'red' : 'blue') ?>
            <?php else: ?><?= badge('Off', 'gray') ?><?php endif; ?></td>
        <td><?= (int)$a['is_active'] === 1 ? badge('Active', 'green') : badge('Inactive', 'red') ?></td>
        <td><div class="actions">
          <a class="btn btn-sm" href="update-manager.php?app=<?= (int)$a['id'] ?>" title="Update dialog"><i class="fa-solid fa-sliders"></i></a>
          <a class="btn btn-sm" href="edit-application.php?id=<?= (int)$a['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="toggle">
            <button class="btn btn-sm <?= (int)$a['is_active'] === 1 ? '' : 'btn-ok' ?>" type="submit" title="Activate / deactivate"><i class="fa-solid fa-power-off"></i></button></form>
          <form method="post" class="inline" data-confirm="Delete <?= e($a['name']) ?> and all of its update configuration and history? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-danger" type="submit" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
        </div></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
