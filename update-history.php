<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Update History'; $active = 'history';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore') {
    csrf_require();
    $st = $pdo->prepare('SELECT * FROM update_history WHERE id = ?');
    $st->execute([(int)($_POST['history_id'] ?? 0)]);
    $h = $st->fetch();
    if ($h) {
        $snap = json_decode($h['config_snapshot'], true);
        [$clean, $errs] = is_array($snap) ? validate_config($snap) : [[], ['Snapshot is unreadable.']];
        if (!$errs) {
            try {
                save_update_config((int)$h['application_id'], $clean, $admin);
                flash('success', 'Configuration #' . (int)$h['id'] . ' restored and now live.');
            } catch (Throwable $t) {
                error_log('DEVIL EX restore: ' . $t->getMessage());
                flash('danger', 'Could not restore this configuration.');
            }
        } else {
            flash('danger', 'This snapshot can no longer be restored: ' . implode(' ', $errs));
        }
    } else {
        flash('danger', 'History record not found.');
    }
    redirect('update-history.php');
}

$appFilter = (int)($_GET['app'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;
$where = $appFilter > 0 ? 'WHERE h.application_id = ' . $appFilter : '';
$total = (int)$pdo->query("SELECT COUNT(*) FROM update_history h $where")->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);
$rows = $pdo->query("SELECT h.*, a.name AS app_name FROM update_history h JOIN applications a ON a.id = h.application_id $where
                     ORDER BY h.id DESC LIMIT $per OFFSET " . (($page - 1) * $per))->fetchAll();
$apps = $pdo->query('SELECT id, name FROM applications ORDER BY name')->fetchAll();

$view = null; $viewSnap = [];
if (!empty($_GET['view'])) {
    $st = $pdo->prepare('SELECT h.*, a.name AS app_name FROM update_history h JOIN applications a ON a.id = h.application_id WHERE h.id = ?');
    $st->execute([(int)$_GET['view']]);
    $view = $st->fetch() ?: null;
    $viewSnap = $view ? (json_decode($view['config_snapshot'], true) ?: []) : [];
}
$statusCls = ['enabled' => 'green', 'disabled' => 'gray', 'maintenance' => 'amber', 'changed' => 'blue'];
$qs = $appFilter ? '&app=' . $appFilter : '';
require __DIR__ . '/includes/header.php';
?>
<?php if ($view): ?>
<div class="card">
  <div class="card-head"><h2><i class="fa-solid fa-file-lines"></i> Configuration #<?= (int)$view['id'] ?> · <?= e($view['app_name']) ?></h2>
    <div class="actions">
      <form method="post" data-confirm="Restore this configuration and make it live for the API?"><?= csrf_field() ?><input type="hidden" name="action" value="restore"><input type="hidden" name="history_id" value="<?= (int)$view['id'] ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-rotate-left"></i> Restore this configuration</button></form>
      <a class="btn btn-sm" href="update-history.php<?= $appFilter ? '?app=' . $appFilter : '' ?>">Close</a></div></div>
  <p class="muted small">Saved <?= e(fmt_date($view['created_at'])) ?> by <?= e($view['admin_name']) ?></p>
  <dl class="kv"><?php foreach ($viewSnap as $k => $v): ?><dt><?= e(ucwords(str_replace('_', ' ', (string)$k))) ?></dt><dd><?= is_scalar($v) ? e($v) : '' ?></dd><?php endforeach; ?></dl>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Change log (<?= $total ?>)</h2>
    <form method="get"><select name="app" data-app-switch><option value="0">All applications</option>
      <?php foreach ($apps as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $appFilter === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></form></div>
  <?php if (!$rows): ?><p class="muted">No history yet. Changes appear here when you save an update configuration.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Application</th><th>Previous</th><th>New</th><th>Type</th><th>Admin</th><th>Updated</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $h): ?>
      <tr><td><b><?= e($h['app_name']) ?></b></td><td><?= e($h['previous_version'] ?? '—') ?></td><td><b><?= e($h['new_version']) ?></b></td>
        <td><?= badge(ucfirst($h['update_type']), $h['update_type'] === 'mandatory' ? 'red' : 'blue') ?></td><td><?= e($h['admin_name']) ?></td><td><?= e(fmt_date($h['created_at'])) ?></td>
        <td><?= badge(ucfirst($h['status']), $statusCls[$h['status']] ?? 'gray') ?></td>
        <td><a class="btn btn-sm" href="update-history.php?view=<?= (int)$h['id'] . $qs ?>"><i class="fa-solid fa-eye"></i> View</a></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php if ($pages > 1): ?><div class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><?= $i === $page ? '<span>' . $i . '</span>' : '<a href="update-history.php?page=' . $i . $qs . '">' . $i . '</a>' ?><?php endfor; ?></div><?php endif; ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
