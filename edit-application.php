<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Edit Application'; $active = 'apps';
$id  = (int)($_GET['id'] ?? 0);
$cur = $id > 0 ? app_by_pk($id) : null;
if (!$cur) {
    flash('danger', 'Application not found.');
    redirect('applications.php');
}
$errors = [];
$app = $cur;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    [$new, $errors] = validate_app($_POST);
    $app = array_merge($cur, $new);
    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM applications WHERE package_name = ? AND id <> ?');
        $st->execute([$new['package_name'], $id]);
        if ($st->fetchColumn()) {
            $errors[] = 'Another application already uses this package name.';
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
            db()->prepare('UPDATE applications SET name=?, package_name=?, current_version=?, latest_version=?, min_supported_version=?, download_url=?, description=?, is_active=?, icon_path=? WHERE id=?')
                ->execute([$new['name'], $new['package_name'], $new['current_version'], $new['latest_version'], $new['min_supported_version'],
                           $new['download_url'] ?: null, $new['description'] ?: null, $new['is_active'], $icon ?? $cur['icon_path'], $id]);
            if ($icon) {
                delete_icon($cur['icon_path']);
            }
            flash('success', 'Application updated.');
            redirect('edit-application.php?id=' . $id);
        } catch (Throwable $t) {
            delete_icon($icon);
            error_log('DEVIL EX edit app: ' . $t->getMessage());
            $errors[] = 'Could not save changes. Please try again.';
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <div class="card-head"><h2><i class="fa-solid fa-pen"></i> <?= e($cur['name']) ?></h2>
    <a class="btn btn-sm btn-primary" href="update-manager.php?app=<?= $id ?>"><i class="fa-solid fa-sliders"></i> Update dialog</a></div>
  <div class="copy-line"><span class="tag">APP ID</span><span class="mono"><?= e($cur['app_id']) ?></span><button type="button" class="btn btn-sm" data-copy="<?= e($cur['app_id']) ?>"><i class="fa-regular fa-copy"></i> Copy</button></div>
  <?php if ($errors): ?><div class="alert alert-danger mt"><i class="fa-solid fa-circle-exclamation"></i><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="mt"><?php $isEdit = true; require __DIR__ . '/includes/app_form.php'; ?></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
