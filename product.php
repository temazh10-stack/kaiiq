<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.*, b.name AS brand_name, b.country AS brand_country, c.name AS category_name
     FROM products p
     LEFT JOIN brands b ON b.id_brand = p.id_brand
     LEFT JOIN categories c ON c.id_category = p.id_category
     WHERE p.id_product = ?'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Товар не найден — Project Kai';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container section text-center"><h1>Товар не найден</h1><p><a class="btn btn-dark" href="' . e(url('catalog.php')) . '">Назад в каталог</a></p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    verify_csrf();

    if ($product['stock_quantity'] > 0) {
        cart_add($product['id_product'], (int) ($_POST['quantity'] ?? 1));
        flash_set('«' . $product['name'] . '» добавлен в корзину.', 'success');
        header('Location: ' . url('cart.php'));
        exit;
    }
}

$isFav = false;
if (is_logged_in()) {
    $f = $pdo->prepare('SELECT 1 FROM favorites WHERE id_user = ? AND id_product = ?');
    $f->execute([$_SESSION['user_id'], $product['id_product']]);
    $isFav = (bool) $f->fetchColumn();
}

$reviewsStmt = $pdo->prepare(
    'SELECT r.*, u.first_name, u.last_name, u.city FROM reviews r
     JOIN user u ON u.id_user = r.id_user
     WHERE r.id_product = ? ORDER BY r.created_at DESC'
);
$reviewsStmt->execute([$product['id_product']]);
$productReviews = $reviewsStmt->fetchAll();

$pageTitle = $product['name'] . ' — Project Kai';
require __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:48px;" class="product-detail">
    <div class="product-card__image" style="aspect-ratio:auto; height:560px;">
      <img src="<?= e(product_image($product['image_url'])) ?>" alt="<?= e($product['name']) ?>" style="height:100%; object-fit:cover;">
      <div class="product-card__badges">
        <span class="badge badge--year"><?= e($product['production_year']) ?></span>
        <span class="<?= condition_badge_class($product['condition']) ?>"><?= condition_label($product['condition']) ?></span>
      </div>
    </div>

    <div>
      <div class="product-card__brand"><?= e($product['brand_name']) ?><?= $product['brand_country'] ? ' &middot; ' . e($product['brand_country']) : '' ?></div>
      <h1><?= e($product['name']) ?></h1>
      <p class="product-card__price" style="font-size:26px; margin-bottom:16px;"><?= format_price($product['price']) ?></p>
      <p><?= nl2br(e($product['description'])) ?></p>

      <ul style="margin: 20px 0; font-size:14px; color:var(--color-text-muted);">
        <li>Категория: <?= e($product['category_name']) ?></li>
        <li>Размер: <?= e($product['size']) ?></li>
        <li><?= $product['stock_quantity'] > 0 ? e($product['stock_quantity']) . ' шт. в наличии' : 'Сейчас нет в наличии' ?></li>
      </ul>

      <div class="flex gap-8" style="flex-wrap:wrap; align-items:flex-start;">
        <form method="post" class="flex gap-8" style="flex-wrap:wrap;">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_to_cart">
          <?php if ($product['stock_quantity'] > 0): ?>
            <select name="quantity" style="padding:12px 14px; border:1px solid var(--color-border); border-radius:2px; background:var(--color-bg-alt);">
              <?php for ($i = 1; $i <= min(10, (int) $product['stock_quantity']); $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <?php endfor; ?>
            </select>
          <?php endif; ?>
          <button type="submit" class="btn btn-dark" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
            <?= $product['stock_quantity'] > 0 ? 'Добавить в корзину' : 'Продано' ?>
          </button>
        </form>
        <button class="btn btn-outline fav-btn <?= $isFav ? 'is-active' : '' ?>" data-product-id="<?= (int) $product['id_product'] ?>">
          <?= $isFav ? icon('heart-filled') . ' В избранном' : icon('heart') . ' Добавить в избранное' ?>
        </button>
      </div>
    </div>
  </div>

  <div style="margin-top:64px;">
    <h2>Отзывы об этом товаре</h2>
    <?php if (!$productReviews): ?>
      <p style="color:var(--color-text-muted);">Отзывов пока нет.</p>
    <?php else: ?>
      <?php foreach ($productReviews as $r): ?>
        <div class="review-card">
          <div style="flex:1;">
            <div class="review-card__head">
              <div>
                <div class="review-card__name"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
                <div class="review-card__location"><?= e($r['city']) ?></div>
              </div>
              <div class="review-card__meta">
                <div class="stars"><?= star_rating($r['rating']) ?></div>
                <?= date('d.m.Y', strtotime($r['created_at'])) ?>
              </div>
            </div>
            <p style="margin-top:10px;"><?= e($r['comment']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
