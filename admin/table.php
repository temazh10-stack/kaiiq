<?php
$pageTitle = 'Управление таблицей';
require __DIR__ . '/_layout_top.php';

$tableKey = $_GET['table'] ?? '';
if (!isset($tables[$tableKey])) {
    http_response_code(404);
    echo '<div class="admin-card">Неизвестная таблица.</div>';
    require __DIR__ . '/_layout_bottom.php';
    exit;
}

$def = $tables[$tableKey];
$pk = $def['pk'];
$columns = $def['columns'];

// ---- load FK option lists ------------------------------------------------
$fkOptions = [];
foreach ($columns as $col => $meta) {
    if ($meta['type'] === 'fk') {
        $rows = $pdo->query("SELECT {$meta['fk_pk']} AS id, {$meta['fk_label']} AS label FROM {$meta['fk_table']} ORDER BY {$meta['fk_label']}")->fetchAll();
        $fkOptions[$col] = $rows;
    }
}

// ---- handle write actions -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $pdo->prepare("DELETE FROM `$tableKey` WHERE `$pk` = ?")->execute([$id]);
        flash_set('Запись удалена: ' . $def['label'] . '.', 'success');
        header('Location: ' . url('admin/table.php?table=' . urlencode($tableKey)));
        exit;
    }

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $fields = [];
        $values = [];

        foreach ($columns as $col => $meta) {
            if (!empty($meta['virtual'])) continue;
            $val = trim($_POST[$col] ?? '');
            if ($meta['type'] === 'fk') {
                $fields[] = "`$col` = ?";
                $values[] = $val !== '' ? (int) $val : null;
            } else {
                $fields[] = "`$col` = ?";
                $values[] = $val !== '' ? $val : null;
            }
        }

        if ($tableKey === 'user') {
            $newPassword = trim($_POST['password'] ?? '');
            if ($newPassword !== '') {
                $fields[] = '`password_hash` = ?';
                $values[] = password_hash($newPassword, PASSWORD_DEFAULT);
            } elseif (!$id) {
                flash_set('Для нового пользователя нужно указать пароль.', 'error');
                header('Location: ' . url('admin/table.php?table=user'));
                exit;
            }
        }

        if ($id) {
            $sql = "UPDATE `$tableKey` SET " . implode(', ', $fields) . " WHERE `$pk` = ?";
            $values[] = $id;
            $pdo->prepare($sql)->execute($values);
            flash_set('Запись обновлена: ' . $def['label'] . '.', 'success');
        } else {
            // build column list matching $values order (excluding virtual fields, password handled above)
            $insertCols = [];
            foreach ($columns as $col => $meta) {
                if (!empty($meta['virtual'])) continue;
                $insertCols[] = $col;
            }
            if ($tableKey === 'user') {
                $insertCols[] = 'password_hash';
            }
            $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
            $quotedCols = array_map(function ($c) { return "`$c`"; }, $insertCols);
            $sql = "INSERT INTO `$tableKey` (" . implode(',', $quotedCols) . ") VALUES ($placeholders)";
            $pdo->prepare($sql)->execute($values);
            flash_set('Запись создана: ' . $def['label'] . '.', 'success');
        }

        header('Location: ' . url('admin/table.php?table=' . urlencode($tableKey)));
        exit;
    }
}

// ---- load record for edit --------------------------------------------------
$editRecord = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM `$tableKey` WHERE `$pk` = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editRecord = $stmt->fetch() ?: null;
}

$rows = $pdo->query("SELECT * FROM `$tableKey` ORDER BY {$def['order_by']}")->fetchAll();

function render_field($col, $meta, $value) {
    $id = 'f_' . $col;
    echo '<div class="field">';
    echo '<label for="' . e($id) . '">' . e($meta['label']) . '</label>';

    if ($meta['type'] === 'textarea') {
        echo '<textarea id="' . e($id) . '" name="' . e($col) . '"' . (!empty($meta['required']) ? ' data-required' : '') . '>' . e($value) . '</textarea>';
    } elseif ($meta['type'] === 'enum') {
        echo '<select id="' . e($id) . '" name="' . e($col) . '">';
        foreach ($meta['options'] as $opt) {
            echo '<option value="' . e($opt) . '"' . ($value === $opt ? ' selected' : '') . '>' . e($opt) . '</option>';
        }
        echo '</select>';
    } elseif ($meta['type'] === 'fk') {
        global $fkOptions;
        echo '<select id="' . e($id) . '" name="' . e($col) . '"' . (!empty($meta['required']) ? ' data-required' : '') . '>';
        echo '<option value="">&mdash; нет &mdash;</option>';
        foreach ($fkOptions[$col] as $opt) {
            echo '<option value="' . (int) $opt['id'] . '"' . ((string) $value === (string) $opt['id'] ? ' selected' : '') . '>#' . (int) $opt['id'] . ' — ' . e($opt['label']) . '</option>';
        }
        echo '</select>';
    } elseif ($meta['type'] === 'password') {
        echo '<input type="password" id="' . e($id) . '" name="' . e($col) . '" autocomplete="new-password">';
    } elseif ($meta['type'] === 'number') {
        echo '<input type="number" step="' . e($meta['step'] ?? '1') . '" id="' . e($id) . '" name="' . e($col) . '" value="' . e($value) . '"' . (!empty($meta['required']) ? ' data-required' : '') . '>';
    } elseif ($meta['type'] === 'email') {
        echo '<input type="email" id="' . e($id) . '" name="' . e($col) . '" value="' . e($value) . '"' . (!empty($meta['required']) ? ' data-required' : '') . '>';
    } else {
        echo '<input type="text" id="' . e($id) . '" name="' . e($col) . '" value="' . e($value) . '"' . (!empty($meta['required']) ? ' data-required' : '') . '>';
    }
    echo '</div>';
}
?>
<div class="admin-topbar">
  <h1 class="mt-0"><?= e($def['label']) ?></h1>
</div>

<div class="admin-card">
  <h2 class="mt-0"><?= $editRecord ? 'Редактирование записи #' . (int) $editRecord[$pk] : 'Добавить запись' ?></h2>
  <form method="post" data-validate novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editRecord ? (int) $editRecord[$pk] : '' ?>">

    <div class="form-row-split" style="flex-wrap:wrap;">
      <?php foreach ($columns as $col => $meta): ?>
        <div style="flex:1; min-width:220px;">
          <?php render_field($col, $meta, $editRecord[$col] ?? ''); ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-dark"><?= $editRecord ? 'Сохранить изменения' : 'Создать' ?></button>
    <?php if ($editRecord): ?><a href="<?= url('admin/table.php?table=' . $tableKey) ?>" class="btn btn-outline">Отмена</a><?php endif; ?>
  </form>
</div>

<div class="admin-card">
  <h2 class="mt-0">Все записи (<?= count($rows) ?>)</h2>
  <div class="table-scroll">
    <table class="admin-table">
      <thead>
        <tr>
          <?php foreach ($def['list_columns'] as $col): ?><th><?= e($columns[$col]['label'] ?? $col) ?></th><?php endforeach; ?>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($def['list_columns'] as $col): ?>
              <td><?= e($row[$col] ?? '') ?></td>
            <?php endforeach; ?>
            <td class="admin-actions">
              <a class="btn btn-sm btn-outline" href="<?= url('admin/table.php?table=' . $tableKey . '&edit=' . (int) $row[$pk]) ?>">Изменить</a>
              <form method="post" onsubmit="return confirm('Удалить эту запись?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $row[$pk] ?>">
                <button type="submit" class="btn btn-sm" style="background:#a63d3d; color:#fff;">Удалить</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
