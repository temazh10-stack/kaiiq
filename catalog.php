<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/api_helpers.php';

$pageTitle = 'Каталог — Project Kai';

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT * FROM brands ORDER BY name')->fetchAll();
$brandFlags = get_country_flags(array_column($brands, 'country'));

// ---- read filters from query string -------------------------------------
$inStock   = isset($_GET['in_stock']);
$category  = trim($_GET['category'] ?? '');
$brandIds  = array_filter(array_map('intval', $_GET['brand'] ?? []));
$yearFrom  = ($_GET['year_from'] ?? '') !== '' ? (int) $_GET['year_from'] : null;
$yearTo    = ($_GET['year_to'] ?? '') !== '' ? (int) $_GET['year_to'] : null;
$size      = trim($_GET['size'] ?? '');
$priceMin  = ($_GET['price_min'] ?? '') !== '' ? (float) $_GET['price_min'] : null;
$priceMax  = ($_GET['price_max'] ?? '') !== '' ? (float) $_GET['price_max'] : null;
$condition = trim($_GET['condition'] ?? '');
$sort      = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];

if ($inStock) {
    $where[] = 'p.stock_quantity > 0';
}
if ($category !== '') {
    $where[] = 'c.name = ?';
    $params[] = $category;
}
if ($brandIds) {
    $placeholders = implode(',', array_fill(0, count($brandIds), '?'));
    $where[] = "p.id_brand IN ($placeholders)";
    foreach ($brandIds as $bid) { $params[] = $bid; }
}
if ($yearFrom !== null) {
    $where[] = 'p.production_year >= ?';
    $params[] = $yearFrom;
}
if ($yearTo !== null) {
    $where[] = 'p.production_year <= ?';
    $params[] = $yearTo;
}
if ($size !== '') {
    $where[] = 'p.size = ?';
    $params[] = $size;
}
if ($priceMin !== null) {
    $where[] = 'p.price >= ?';
    $params[] = $priceMin;
}
if ($priceMax !== null) {
    $where[] = 'p.price <= ?';
    $params[] = $priceMax;
}
if ($condition !== '') {
    $where[] = 'p.`condition` = ?';
    $params[] = $condition;
}

switch ($sort) {
    case 'price_asc':  $orderBy = 'p.price ASC'; break;
    case 'price_desc': $orderBy = 'p.price DESC'; break;
    case 'year_desc':  $orderBy = 'p.production_year DESC'; break;
    default:           $orderBy = 'p.id_product DESC';
}

$sql = 'SELECT p.*, b.name AS brand_name, c.name AS category_name
        FROM products p
        LEFT JOIN brands b ON b.id_brand = p.id_brand
        LEFT JOIN categories c ON c.id_category = p.id_category';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$userFavorites = [];
if (is_logged_in()) {
    $stmt2 = $pdo->prepare('SELECT id_product FROM favorites WHERE id_user = ?');
    $stmt2->execute([$_SESSION['user_id']]);
    $userFavorites = array_column($stmt2->fetchAll(), 'id_product');
}

function keep_params($except = []) {
    $params = $_GET;
    foreach ($except as $key) {
        unset($params[$key]);
    }
    return $params;
}

require __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <h1>Каталог</h1>
  <p class="subtitle" style="color:var(--color-text-muted); margin-bottom:32px;"><?= count($products) ?> товар<?= plural_ru(count($products), '', 'а', 'ов') ?> в наличии</p>

  <div class="catalog-layout">
    <aside class="filters">
      <form method="get" id="filterForm">
        <input type="hidden" name="size" value="<?= e($size) ?>">
        <input type="hidden" name="sort" value="<?= e($sort) ?>">

        <div class="filter-option">
          <label class="checkbox-row">
            <input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : '' ?> onchange="this.form.submit()">
            Только в наличии
          </label>
        </div>

        <h3>Категория</h3>
        <?php foreach ($categories as $cat): ?>
          <div class="filter-option">
            <label class="checkbox-row">
              <input type="radio" name="category" value="<?= e($cat['name']) ?>" <?= $category === $cat['name'] ? 'checked' : '' ?> onchange="this.form.submit()">
              <?= e($cat['name']) ?>
            </label>
          </div>
        <?php endforeach; ?>
        <?php if ($category !== ''): ?><a class="filter-clear" href="?<?= http_build_query(keep_params(['category'])) ?>">Сбросить</a><?php endif; ?>

        <h3>Бренд</h3>
        <?php foreach ($brands as $b): ?>
          <?php $flag = $brandFlags[$b['country']] ?? null; ?>
          <div class="filter-option">
            <label class="checkbox-row">
              <input type="checkbox" name="brand[]" value="<?= (int) $b['id_brand'] ?>" <?= in_array($b['id_brand'], $brandIds) ? 'checked' : '' ?> onchange="this.form.submit()">
              <?php if ($flag): ?><img src="<?= e($flag['png']) ?>" alt="<?= e($b['country']) ?>" class="brand-flag"><?php endif; ?>
              <?= e($b['name']) ?>
            </label>
          </div>
        <?php endforeach; ?>

        <h3>Год выпуска</h3>
        <div class="field" style="margin-bottom:10px;">
          <label>От: <input type="number" name="year_from" value="<?= e($yearFrom) ?>" placeholder="1990"></label>
        </div>
        <div class="field">
          <label>До: <input type="number" name="year_to" value="<?= e($yearTo) ?>" placeholder="2005"></label>
        </div>

        <h3>Размер</h3>
        <div class="size-pills">
          <?php foreach (['XS','S','M','L','XL','XXL'] as $s): ?>
            <div class="size-pill <?= $size === $s ? 'is-selected' : '' ?>" data-name="size"><?= $s ?></div>
          <?php endforeach; ?>
        </div>

        <h3>Диапазон цен</h3>
        <div class="field" style="margin-bottom:10px;">
          <label>Мин: $<input type="number" name="price_min" value="<?= e($priceMin) ?>" placeholder="0"></label>
        </div>
        <div class="field">
          <label>Макс: $<input type="number" name="price_max" value="<?= e($priceMax) ?>" placeholder="500"></label>
        </div>

        <h3>Состояние</h3>
        <?php foreach (['Excellent','Good','Fair'] as $cnd): ?>
          <div class="filter-option">
            <label class="checkbox-row">
              <input type="radio" name="condition" value="<?= $cnd ?>" <?= $condition === $cnd ? 'checked' : '' ?> onchange="this.form.submit()">
              <?= condition_label($cnd) ?>
            </label>
          </div>
        <?php endforeach; ?>
        <?php if ($condition !== ''): ?><a class="filter-clear" href="?<?= http_build_query(keep_params(['condition'])) ?>">Сбросить</a><?php endif; ?>

        <div style="margin-top:20px;">
          <button type="submit" class="btn btn-outline btn-sm">Применить</button>
          <a href="<?= url('catalog.php') ?>" class="btn btn-sm" style="color:var(--color-text-muted);">Сбросить всё</a>
        </div>
      </form>
    </aside>

    <div>
      <div class="catalog-toolbar">
        <span></span>
        <form method="get" id="sortForm">
          <?php foreach ($_GET as $k => $v) {
            if ($k === 'sort') continue;
            foreach ((array) $v as $vv) {
              echo '<input type="hidden" name="' . e($k) . ($k === 'brand' ? '[]' : '') . '" value="' . e($vv) . '">';
            }
          } ?>
          <select name="sort" class="field" style="padding:10px 14px;border:1px solid var(--color-border);border-radius:2px;background:var(--color-bg-alt);" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
            <option value="year_desc" <?= $sort === 'year_desc' ? 'selected' : '' ?>>Год: сначала новые</option>
          </select>
        </form>
      </div>

      <?php if (!$products): ?>
        <div class="empty-state">По этим фильтрам ничего не найдено. Попробуйте изменить параметры поиска.</div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <?php $isFav = in_array($product['id_product'], $userFavorites); ?>
            <div class="product-card">
              <a href="<?= url('product.php?id=' . (int) $product['id_product']) ?>" class="product-card__image">
                <img src="<?= e(product_image($product['image_url'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                <div class="product-card__badges">
                  <span class="badge badge--year"><?= e($product['production_year']) ?></span>
                  <span class="<?= condition_badge_class($product['condition']) ?>"><?= condition_label($product['condition']) ?></span>
                  <?php if ($product['stock_quantity'] <= 0): ?><span class="badge badge--outofstock">Продано</span><?php endif; ?>
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
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
