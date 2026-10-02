<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$tables = require __DIR__ . '/_config.php';
$views = require __DIR__ . '/_views_config.php';
$currentTable = $_GET['table'] ?? null;
$currentView = $_GET['view'] ?? null;
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = ($pageTitle ?? 'Админ-панель') . ' — Project Kai';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?= url('admin/index.php') ?>" class="logo logo--light">Project Kai Admin</a>

    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.06em; opacity:.6; margin:16px 0 6px;">Таблицы</div>
    <?php foreach ($tables as $key => $t): ?>
      <a href="<?= url('admin/table.php?table=' . $key) ?>" class="<?= ($currentScript === 'table.php' && $currentTable === $key) ? 'is-active' : '' ?>"><?= e($t['label']) ?></a>
    <?php endforeach; ?>

    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.06em; opacity:.6; margin:16px 0 6px;">Представления (только просмотр)</div>
    <?php foreach ($views as $key => $v): ?>
      <a href="<?= url('admin/views.php?view=' . $key) ?>" class="<?= ($currentScript === 'views.php' && $currentView === $key) ? 'is-active' : '' ?>"><?= e($v['label']) ?></a>
    <?php endforeach; ?>

    <div style="margin-top:20px; padding-top:16px; border-top:1px solid rgba(255,255,255,.12);">
      <a href="<?= url('index.php') ?>">&larr; На сайт</a>
      <a href="<?= url('logout.php') ?>">Выйти</a>
    </div>
  </aside>

  <div class="admin-main">
    <?php $flash = flash_get(); ?>
    <?php if ($flash): ?><div class="flash flash--<?= e($flash['type']) ?>" style="margin:-32px -36px 24px; padding:14px 36px;"><?= e($flash['message']) ?></div><?php endif; ?>
