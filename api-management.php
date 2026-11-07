<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'API Management'; $active = 'api';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';
    if ($action === 'regen') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && app_by_pk($id)) {
            $pdo->prepare('UPDATE applications SET app_id = ? WHERE id = ?')->execute([gen_app_id(), $id]);
            flash('success', 'New Application ID generated. Update it inside the Android app.');
        }
    } elseif ($action === 'purge') {
        $n = purge_logs(max(0, (int)($_POST['days'] ?? 30)));
        flash('success', $n . ' old log entries deleted.');
    } elseif ($action === 'clear') {
        $pdo->exec('DELETE FROM api_logs');
        flash('success', 'All API logs cleared.');
    }
    redirect('api-management.php');
}

$apps = $pdo->query('SELECT a.id, a.name, a.package_name, a.app_id, a.is_active,
        (SELECT COUNT(*) FROM api_logs l WHERE l.application_id = a.id) AS reqs,
        (SELECT MAX(l.created_at) FROM api_logs l WHERE l.application_id = a.id) AS last_req,
        (SELECT COUNT(*) FROM app_events e WHERE e.application_id = a.id AND e.event_type = \'download\') AS downloads,
        (SELECT COUNT(*) FROM app_events e WHERE e.application_id = a.id AND e.event_type = \'update\') AS updates
        FROM applications a ORDER BY a.name')->fetchAll();
$byEndpoint = $pdo->query('SELECT endpoint, COUNT(*) n, SUM(status_code >= 400) errs FROM api_logs GROUP BY endpoint ORDER BY n DESC')->fetchAll();
$logs = $pdo->query('SELECT l.*, a.name AS app_name FROM api_logs l LEFT JOIN applications a ON a.id = l.application_id ORDER BY l.id DESC LIMIT 30')->fetchAll();
$base = site_url('api/');
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2><i class="fa-solid fa-plug"></i> Endpoints</h2>
  <?php foreach ([['GET', 'check_update.php?app_id=APP_ID&version_code=100'], ['GET', 'get_app_config.php?app_id=APP_ID'], ['POST', 'report_download.php'], ['POST', 'report_update.php']] as [$m, $p]): ?>
    <div class="copy-line"><span class="tag"><?= e($m) ?></span><span class="mono"><?= e($base . $p) ?></span><button type="button" class="btn btn-sm" data-copy="<?= e($base . $p) ?>"><i class="fa-regular fa-copy"></i></button></div>
  <?php endforeach; ?>
  <p class="muted small">Android guide: <a href="docs.php" target="_blank" rel="noopener">docs.php</a></p>
</div>

<div class="card">
  <h2><i class="fa-solid fa-key"></i> Application IDs</h2>
  <?php if (!$apps): ?><p class="muted">No applications yet.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Application</th><th>Application ID</th><th>Requests</th><th>Downloads</th><th>Updates</th><th>Last request</th><th></th></tr></thead><tbody>
    <?php foreach ($apps as $a): ?>
      <tr><td><b><?= e($a['name']) ?></b><div class="muted small"><?= $a['is_active'] ? 'Active' : 'Inactive' ?></div></td>
        <td><span class="mono"><?= e($a['app_id']) ?></span> <button type="button" class="btn btn-sm" data-copy="<?= e($a['app_id']) ?>"><i class="fa-regular fa-copy"></i></button></td>
        <td><?= number_format((int)$a['reqs']) ?></td><td><?= number_format((int)$a['downloads']) ?></td><td><?= number_format((int)$a['updates']) ?></td><td><?= e(fmt_date($a['last_req'])) ?></td>
        <td><form method="post" data-confirm="Generate a new ID for <?= e($a['name']) ?>? Installed apps using the old ID will stop receiving updates until you ship the new ID."><?= csrf_field() ?><input type="hidden" name="action" value="regen"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-sm btn-danger" type="submit"><i class="fa-solid fa-rotate"></i> Regenerate</button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>

<div class="split">
  <div class="card">
    <div class="card-head"><h2><i class="fa-solid fa-list-check"></i> Recent API requests</h2></div>
    <?php if (!$logs): ?><p class="muted">No requests logged yet.</p><?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Time</th><th>Endpoint</th><th>App</th><th>Ver</th><th>Status</th><th>IP</th></tr></thead><tbody>
      <?php foreach ($logs as $l): ?>
        <tr><td><?= e(fmt_date($l['created_at'])) ?></td><td class="mono"><?= e($l['endpoint']) ?></td><td><?= e($l['app_name'] ?? '—') ?></td><td><?= e($l['version_code'] ?? '—') ?></td>
          <td><?= badge((string)$l['status_code'], (int)$l['status_code'] < 400 ? 'green' : 'red') ?></td><td class="mono"><?= e($l['ip_address']) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </div>
  <div>
    <div class="card"><h2><i class="fa-solid fa-chart-pie"></i> By endpoint</h2>
      <?php if (!$byEndpoint): ?><p class="muted">No data yet.</p><?php endif; ?>
      <?php foreach ($byEndpoint as $b): ?><div class="status-row"><b class="mono"><?= e($b['endpoint']) ?></b><span class="muted" style="margin-left:auto"><?= number_format((int)$b['n']) ?> · <?= (int)$b['errs'] ?> errors</span></div><?php endforeach; ?></div>
    <div class="card"><h2><i class="fa-solid fa-broom"></i> Maintenance</h2>
      <form method="post" class="actions" data-confirm="Delete log entries older than the chosen number of days?"><?= csrf_field() ?><input type="hidden" name="action" value="purge">
        <input type="number" name="days" min="0" max="3650" value="<?= e(get_setting('log_retention_days', '30')) ?>" style="max-width:100px"><span class="muted small">days</span><button class="btn btn-sm" type="submit">Delete older logs</button></form>
      <form method="post" class="mt" data-confirm="Delete ALL API logs?"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-sm btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Clear all logs</button></form></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
