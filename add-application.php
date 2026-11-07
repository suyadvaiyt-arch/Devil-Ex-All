<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Add New Application'; $active = 'add';
$errors = [];
$app = ['name' => '', 'package_name' => '', 'current_version' => '1.0.0', 'latest_version' => '1.0.0', 'min_supported_version' => '1.0.0',
        'download_url' => '', 'description' => '', 'is_active' => 1, 'icon_path' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    [$app, $errors] = validate_app($_POST);
    $app['icon_path'] = null;
    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM applications WHERE package_name = ?');
        $st->execute([$app['package_name']]);
        if ($st->fetchColumn()) {
            $errors[] = 'An application with this package name already exists.';
        }
    }
    $icon = null;
    if (!$errors) {
        [$icon, $iconErr] = handle_icon_upload($_FILES['icon'] ?? []);
        if ($iconErr) {
            $errors[] = $iconErr;
        }
    }
    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO applications (app_id, name, package_name, current_version, latest_version, min_supported_version, icon_path, download_url, description, is_active)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([gen_app_id(), $app['name'], $app['package_name'], $app['current_version'], $app['latest_version'],
                           $app['min_supported_version'], $icon, $app['download_url'] ?: null, $app['description'] ?: null, $app['is_active']]);
            $pk = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO update_configs (application_id, latest_version, download_url, updated_by) VALUES (?, ?, ?, ?)')
                ->execute([$pk, $app['latest_version'], $app['download_url'] ?: null, $admin['id']]);
            $pdo->commit();
            flash('success', 'Application created. Now configure its update dialog.');
            redirect('update-manager.php?app=' . $pk);
        } catch (Throwable $t) {
            if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
            delete_icon($icon);
            error_log('DEVIL EX add app: ' . $t->getMessage());
            $errors[] = 'Could not save the application. Please try again.';
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2><i class="fa-solid fa-plus"></i> New application</h2>
  <?php if ($errors): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <?php $isEdit = false; require __DIR__ . '/includes/app_form.php'; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
