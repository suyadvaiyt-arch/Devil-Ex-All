<?php
require_once __DIR__ . '/includes/init.php';
$admin = require_login();
$layout = 'admin'; $pageTitle = 'Dashboard'; $active = 'dashboard';
$pdo = db();

$stats = [
    ['Total Applications',      (int)$pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn(), 'mobile-screen'],
    ['Active Applications',     (int)$pdo->query('SELECT COUNT(*) FROM applications WHERE is_active = 1')->fetchColumn(), 'circle-check'],
    ['Update Configurations',   (int)$pdo->query('SELECT COUNT(*) FROM update_configs')->fetchColumn(), 'sliders'],
    ['Mandatory Updates',       (int)$pdo->query("SELECT COUNT(*) FROM update_configs WHERE dialog_enabled = 1 AND update_type = 'mandatory'")->fetchColumn(), 'lock'],
    ['Optional Updates',        (int)$pdo->query("SELECT COUNT(*) FROM update_configs WHERE dialog_enabled = 1 AND update_type = 'optional'")->fetchColumn(), 'bell'],
    ['Total API Requests',      (int)$pdo->query('SELECT COUNT(*) FROM api_logs')->fetchColumn(), 'chart-line'],
];

$perDay = [];
foreach ($pdo->query('SELECT DATE(created_at) d, COUNT(*) n FROM api_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)') as $r) {
    $perDay[$r['d']] = (int)$r['n'];
}
$days = []; $maxN = 1;
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $days[$d] = $perDay[$d] ?? 0;
    $maxN = max($maxN, $days[$d]);
}
$recent = $pdo->query('SELECT h.*, a.name AS app_name FROM update_history h JOIN applications a ON a.id = h.application_id ORDER BY h.id DESC LIMIT 6')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="stats">
  <?php foreach ($stats as [$label, $n, $icon]): ?>
    <div class="stat glass"><div class="ic"><i class="fa-solid fa-<?= e($icon) ?>"></i></div><div><b><?= number_format($n) ?></b><span><?= e($label) ?></span></div></div>
  <?php endforeach; ?>
</div>
<div class="split">
  <div class="card">
    <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent configuration changes</h2>
    <?php if (!$recent): ?><p class="muted">No changes yet. <a href="add-application.php">Add your first application</a>.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
      <thead><tr><th>App</th><th>Version</th><th>Type</th><th>By</th><th>When</th></tr></thead><tbody>
      <?php foreach ($recent as $h): ?>
        <tr><td><?= e($h['app_name']) ?></td><td><?= e($h['previous_version'] ?? '—') ?> &rarr; <b><?= e($h['new_version']) ?></b></td>
        <td><?= badge(ucfirst($h['update_type']), $h['update_type'] === 'mandatory' ? 'red' : 'blue') ?></td><td><?= e($h['admin_name']) ?></td><td><?= e(fmt_date($h['created_at'])) ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2><i class="fa-solid fa-chart-column"></i> API requests · 7 days</h2>
    <div class="bars">
      <?php foreach ($days as $d => $n): ?>
        <div title="<?= e($d) ?>: <?= $n ?>"><span><?= $n ?></span><i style="height:<?= max(3, (int)round($n / $maxN * 100)) ?>%"></i><span><?= e(date('D', strtotime($d))) ?></span></div>
      <?php endforeach; ?>
    </div>
    <div class="actions mt"><a class="btn btn-primary btn-sm" href="update-manager.php"><i class="fa-solid fa-sliders"></i> Update dialogs</a><a class="btn btn-sm" href="api-management.php"><i class="fa-solid fa-plug"></i> API</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
