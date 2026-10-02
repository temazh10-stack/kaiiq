<?php
$pageTitle = 'Представление';
require __DIR__ . '/_layout_top.php';

$viewKey = $_GET['view'] ?? '';
if (!isset($views[$viewKey])) {
    http_response_code(404);
    echo '<div class="admin-card">Неизвестное представление.</div>';
    require __DIR__ . '/_layout_bottom.php';
    exit;
}

$def = $views[$viewKey];
$rows = $pdo->query("SELECT * FROM `$viewKey`")->fetchAll();
$columns = $rows ? array_keys($rows[0]) : [];
?>
<div class="admin-topbar">
  <h1 class="mt-0"><?= e($def['label']) ?></h1>
</div>

<div class="admin-card">
  <p style="color:var(--color-text-muted); margin-top:0;"><?= e($def['description']) ?></p>
  <code style="display:block; background:var(--color-bg); padding:10px 14px; border-radius:2px; font-size:12px; margin-bottom:16px;">SELECT * FROM <?= e($viewKey) ?>;</code>

  <?php if (!$rows): ?>
    <div class="empty-state">Это представление сейчас не возвращает строк.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="admin-table">
        <thead><tr><?php foreach ($columns as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($columns as $col): ?><td><?= e($row[$col]) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
