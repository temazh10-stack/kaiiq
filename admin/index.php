<?php
$pageTitle = 'Панель управления';
require __DIR__ . '/_layout_top.php';

$counts = [];
foreach ($tables as $key => $t) {
    $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM `$key`")->fetchColumn();
}
?>
<div class="admin-topbar">
  <h1 class="mt-0">Панель управления</h1>
</div>

<div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
  <?php foreach ($tables as $key => $t): ?>
    <a href="<?= url('admin/table.php?table=' . $key) ?>" class="summary-card" style="display:block;">
      <div class="label"><?= e($t['label']) ?></div>
      <div class="value"><?= $counts[$key] ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="admin-card" style="margin-top:28px;">
  <h2 class="mt-0">Вы вошли как</h2>
  <p><?= e($_SESSION['name'] ?? '') ?> &middot; <?= e($_SESSION['role'] ?? '') ?></p>
  <p style="color:var(--color-text-muted); font-size:14px;">Используйте меню слева, чтобы управлять любой таблицей (добавлять / изменять / удалять записи) или просматривать пять представлений с готовыми отчётами для этого проекта.</p>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
