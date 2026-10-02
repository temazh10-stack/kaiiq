<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Project Kai — Новинки';

$userFavorites = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare('SELECT id_product FROM favorites WHERE id_user = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $userFavorites = array_column($stmt->fetchAll(), 'id_product');
}

$products = $pdo->query(
    'SELECT p.*, b.name AS brand_name FROM products p
     LEFT JOIN brands b ON b.id_brand = p.id_brand
     ORDER BY p.id_product DESC LIMIT 6'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="hero" style="background-image: url('<?= url('assets/img/site/hero.svg') ?>');">
  <div class="container">
    <div class="hero-content">
      <h1>Новинки</h1>
      <p>Отобранные вещи золотой эпохи моды. Каждая рассказывает свою историю стиля, который не выходит из моды.</p>
      <a href="<?= url('catalog.php') ?>" class="btn btn-outline-light" style="margin-top: 20px;">Смотреть коллекцию &rarr;</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Свежие поступления</h2>
        <p class="subtitle">Только что добавлено в коллекцию</p>
      </div>
      <a href="<?= url('catalog.php') ?>" class="link-arrow">Смотреть все &rarr;</a>
    </div>

    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <?php $isFav = in_array($product['id_product'], $userFavorites); ?>
        <div class="product-card">
          <a href="<?= url('product.php?id=' . (int) $product['id_product']) ?>" class="product-card__image">
            <img src="<?= e(product_image($product['image_url'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
            <div class="product-card__badges">
              <span class="badge badge--year"><?= e($product['production_year']) ?></span>
              <span class="<?= condition_badge_class($product['condition']) ?>"><?= condition_label($product['condition']) ?></span>
            </div>
          </a>
          <button class="fav-btn <?= $isFav ? 'is-active' : '' ?>" data-product-id="<?= (int) $product['id_product'] ?>" aria-label="Добавить в избранное">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.8"><path d="M12.1 20.3c-.2.1-.4.1-.6 0C7.5 17.9 3 14.6 3 10.1 3 7.3 5.2 5 8 5c1.6 0 3 .8 4 2 1-1.2 2.4-2 4-2 2.8 0 5 2.3 5 5.1 0 4.5-4.5 7.8-8.9 10.2z"/></svg>
          </button>
          <div class="product-card__brand"><?= e($product['brand_name']) ?></div>
          <div class="product-card__title-row">
            <span class="product-card__title"><?= e($product['name']) ?></span>
            <span class="product-card__price"><?= format_price($product['price']) ?></span>
          </div>
          <div class="product-card__size">Размер: <?= e($product['size']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="container" style="margin-bottom: 64px;">
  <div class="dark-panel archive-grid">
    <div>
      <h2>Архив</h2>
      <p>Project Kai существует на стыке памяти и стиля. Мы находим редкие винтажные вещи 90-х и начала 2000-х, каждая из которых проверена на подлинность и сохранена с той самой эпохи, когда мода объединяла минимализм и максимализм.</p>
      <p>Каждая вещь в нашей коллекции уже прожила свою жизнь до нас. Выцветшие концертные футболки, идеально изношенный деним, знаковые вещи, определившие поколение. Мы верим, что лучшая мода — не новая, а заново открытая.</p>
    </div>
    <img src="<?= url('assets/img/site/archive.svg') ?>" alt="Уличный стиль — архив" style="border-radius: 2px;">
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
