<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/printful_api.php';

cart_init();
$errors = [];
$printfulCountries = printful_countries(); // null if API key not set / unreachable
$zones = delivery_zones();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_qty') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = (int) ($_POST['quantity'] ?? 1);

        $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE id_product = ?');
        $stmt->execute([$productId]);
        $stock = (int) $stmt->fetchColumn();
        $qty = max(0, min($qty, $stock));

        cart_set_qty($productId, $qty);
        header('Location: ' . url('cart.php'));
        exit;
    }

    if ($action === 'remove') {
        cart_remove((int) ($_POST['product_id'] ?? 0));
        header('Location: ' . url('cart.php'));
        exit;
    }

    if ($action === 'place_order') {
        require_login();

        $items = cart_items($pdo);

        $countryCode = trim($_POST['country_code'] ?? '');
        $stateCode = trim($_POST['state_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $street = trim($_POST['street'] ?? '');

        // The delivery zone is derived from the chosen country, not picked
        // separately — otherwise a customer could pick "Germany" as their
        // country and "По России" as the shipping zone, which makes no sense.
        if ($printfulCountries) {
            $zone = $countryCode !== '' ? zone_for_country($countryCode) : '';
        } else {
            $zone = $_POST['delivery_zone'] ?? '';
        }

        $countryName = $countryCode;
        if ($printfulCountries) {
            foreach ($printfulCountries as $c) {
                if ($c['code'] === $countryCode) {
                    $countryName = $c['name'];
                    if ($stateCode && !empty($c['states'])) {
                        foreach ($c['states'] as $s) {
                            if ($s['code'] === $stateCode) {
                                $stateCode = $s['name'];
                            }
                        }
                    }
                    break;
                }
            }
        }

        $addressParts = array_filter([$countryName, $stateCode, $city, $street]);
        $address = implode(', ', $addressParts);

        if (!$items) {
            $errors['cart'] = 'Ваша корзина пуста.';
        }
        if ($printfulCountries && $countryCode === '') {
            $errors['shipping_address'] = 'Выберите страну доставки.';
        } elseif (!isset($zones[$zone])) {
            $errors['delivery_zone'] = 'Выберите способ доставки.';
        }
        if ($city === '' || $street === '') {
            $errors['shipping_address'] = 'Укажите город и адрес доставки.';
        }

        // Re-check stock right before placing the order.
        foreach ($items as $item) {
            if ($item['quantity'] > (int) $item['stock_quantity']) {
                $errors['cart'] = 'Товара «' . $item['name'] . '» уже нет в нужном количестве. Обновите корзину.';
            }
        }

        if (!$errors) {
            $subtotal = cart_subtotal($items);
            $shippingCost = delivery_cost($zone, $subtotal);
            $total = $subtotal + $shippingCost;

            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                'INSERT INTO orders (id_user, total_amount, shipping_address, shipping_method, shipping_cost, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$_SESSION['user_id'], $total, $address, $zones[$zone]['label'], $shippingCost, 'processing']);
            $orderId = (int) $pdo->lastInsertId();

            $insItem = $pdo->prepare('INSERT INTO order_items (id_order, id_product, quantity, price_at_time) VALUES (?, ?, ?, ?)');
            $updStock = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id_product = ?');

            foreach ($items as $item) {
                $insItem->execute([$orderId, $item['id_product'], $item['quantity'], $item['price']]);
                $updStock->execute([$item['quantity'], $item['id_product']]);
            }

            $pdo->commit();
            cart_clear();

            flash_set('Заказ оформлен! Отследить его можно в разделе «Мои заказы».', 'success');
            header('Location: ' . url('cabinet/orders.php'));
            exit;
        }
    }
}

$items = cart_items($pdo);
$subtotal = cart_subtotal($items);
$countryValue = $_POST['country_code'] ?? '';
$stateValue = $_POST['state_code'] ?? '';
$cityValue = $_POST['city'] ?? '';
$streetValue = $_POST['street'] ?? '';
$selectedZone = $_POST['delivery_zone'] ?? 'domestic'; // only used in the no-API fallback

if ($printfulCountries && $countryValue !== '') {
    $selectedZone = zone_for_country($countryValue);
}

if (!$cityValue && is_logged_in()) {
    $u = current_user($pdo);
    if ($u && $u['city']) {
        $cityValue = $u['city'];
    }
}

$pageTitle = 'Корзина — Project Kai';
require __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1>Корзина</h1>

  <?php if (!$items): ?>
    <p class="subtitle" style="color:var(--color-text-muted); margin-bottom:24px;">В корзине пока пусто.</p>
    <a href="<?= url('catalog.php') ?>" class="btn btn-dark">Перейти в каталог</a>
  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-items">
        <?php foreach ($items as $item): ?>
          <div class="order-line cart-line">
            <img src="<?= e(product_image($item['image_url'])) ?>" alt="<?= e($item['name']) ?>">
            <div class="info">
              <div class="brand"><?= e($item['brand_name']) ?></div>
              <div style="font-weight:600;"><a href="<?= url('product.php?id=' . (int) $item['id_product']) ?>" style="color:inherit;"><?= e($item['name']) ?></a></div>
              <div class="size">Размер: <?= e($item['size']) ?></div>

              <form method="post" class="cart-qty-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_qty">
                <input type="hidden" name="product_id" value="<?= (int) $item['id_product'] ?>">
                <label style="font-size:13px; color:var(--color-text-muted);">
                  Кол-во:
                  <select name="quantity" onchange="this.form.submit()" style="padding:4px 8px; border:1px solid var(--color-border); border-radius:2px; background:var(--color-bg-alt);">
                    <?php for ($i = 1; $i <= max(1, (int) $item['stock_quantity']); $i++): ?>
                      <option value="<?= $i ?>" <?= $i === (int) $item['quantity'] ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                  </select>
                </label>
              </form>
            </div>
            <div style="text-align:right;">
              <div style="font-family:var(--font-serif); font-size:16px;"><?= format_price($item['line_total']) ?></div>
              <form method="post" style="margin-top:8px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="product_id" value="<?= (int) $item['id_product'] ?>">
                <button type="submit" class="btn btn-sm" style="color:var(--color-text-muted); border:1px solid var(--color-border);">Удалить</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="cart-summary admin-card">
        <h2 class="mt-0">Оформление заказа</h2>

        <?php if (isset($errors['cart'])): ?><div class="field-error" style="margin-bottom:12px;"><?= e($errors['cart']) ?></div><?php endif; ?>

        <form method="post" data-validate novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="place_order">

          <?php if (!$printfulCountries): ?>
            <!-- No Printful API key configured: manual zone picker, since
                 there's no country field to derive the zone from. -->
            <h3 style="margin-top:0;">Способ доставки</h3>
            <?php foreach ($zones as $key => $zone): ?>
              <?php $cost = delivery_cost($key, $subtotal); ?>
              <label class="zone-row" style="cursor:pointer; display:flex; align-items:center; gap:12px;">
                <input type="radio" name="delivery_zone" value="<?= e($key) ?>" <?= $selectedZone === $key ? 'checked' : '' ?> required style="width:auto;">
                <span style="flex:1;">
                  <span style="font-weight:600; display:block;"><?= e($zone['label']) ?></span>
                  <span class="zone-days"><?= e($zone['days']) ?></span>
                </span>
                <span class="zone-price"><?= $cost > 0 ? format_price($cost) : 'Бесплатно' ?></span>
              </label>
            <?php endforeach; ?>
            <?php if (isset($errors['delivery_zone'])): ?><div class="field-error"><?= e($errors['delivery_zone']) ?></div><?php endif; ?>

            <p style="font-size:12px; color:var(--color-text-muted); margin-top:12px;">Список стран Printful недоступен (не настроен API-ключ) — страна доставки вводится вручную ниже, поэтому способ доставки выбирается отдельно.</p>
            <input type="hidden" name="country_code" value="">
          <?php else: ?>
            <!-- With Printful available: the zone is derived from the
                 chosen country below, so there's nothing to pick here. -->
            <h3 style="margin-top:0;">Куда доставить</h3>
            <div class="field <?= (isset($errors['shipping_address']) && $countryValue === '') ? 'has-error' : '' ?>" style="margin-bottom:16px;">
              <label for="countrySelect">Страна</label>
              <div class="select-wrap">
                <select name="country_code" id="countrySelect" data-required>
                  <option value="">— выберите страну —</option>
                  <?php foreach ($printfulCountries as $c): ?>
                    <option value="<?= e($c['code']) ?>" <?= $countryValue === $c['code'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if (isset($errors['shipping_address']) && $countryValue === ''): ?><div class="field-error"><?= e($errors['shipping_address']) ?></div><?php endif; ?>
            </div>

            <div class="field" id="stateRow" style="display:none; margin-bottom:16px;">
              <label for="stateSelect">Регион / штат</label>
              <div class="select-wrap">
                <select name="state_code" id="stateSelect">
                  <option value="">—</option>
                </select>
              </div>
            </div>

            <div id="deliveryInfo" class="delivery-card">
              <span class="delivery-card__icon"><?= icon('truck', 22) ?></span>
              <span class="delivery-card__body">
                <span id="deliveryLabel" class="delivery-card__label">Выберите страну — способ доставки определится автоматически</span>
                <span id="deliveryDays" class="delivery-card__days"></span>
              </span>
              <span id="deliveryPrice" class="delivery-card__price"></span>
            </div>
          <?php endif; ?>

          <div class="callout" style="margin-top:16px;">Бесплатная доставка «По России» при заказе от <?= format_price(FREE_SHIPPING_THRESHOLD) ?>.</div>

          <div class="form-row-split">
            <div class="field">
              <label>Город</label>
              <input type="text" name="city" data-required value="<?= e($cityValue) ?>">
            </div>
            <div class="field">
              <label>Улица, дом, квартира</label>
              <input type="text" name="street" data-required value="<?= e($streetValue) ?>" placeholder="ул. Тверская, 10, кв. 5">
            </div>
            <?php if (isset($errors['shipping_address']) && $cityValue !== '' || isset($errors['shipping_address']) && !$printfulCountries): ?><div class="field-error"><?= e($errors['shipping_address']) ?></div><?php endif; ?>
          </div>

          <?php $selectedCost = isset($zones[$selectedZone]) ? delivery_cost($selectedZone, $subtotal) : 0; ?>
          <div style="border-top:1px solid var(--color-border); margin-top:20px; padding-top:16px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
              <span>Товары</span><span id="sumSubtotal"><?= format_price($subtotal) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:var(--color-text-muted); font-size:13px;">
              <span>Доставка</span><span id="sumShipping"><?= $selectedCost > 0 ? format_price($selectedCost) : 'Бесплатно' ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; font-weight:600; font-size:16px; margin-top:8px;">
              <span>Итого</span><span id="sumTotal"><?= format_price($subtotal + $selectedCost) ?></span>
            </div>
          </div>

          <?php if (!is_logged_in()): ?>
            <p style="font-size:13px; color:var(--color-text-muted); margin-top:16px;">Для оформления заказа нужно <a href="<?= url('login.php') ?>" style="color:var(--color-accent);">войти в аккаунт</a>.</p>
          <?php else: ?>
            <button type="submit" class="btn btn-dark btn-block" style="margin-top:20px;">Оформить заказ</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($printfulCountries): ?>
<script>
(function () {
  var countries = <?= json_encode($printfulCountries) ?>;
  var zones = <?= json_encode($zones) ?>;
  var zoneMap = <?= json_encode(zone_map_for_countries($printfulCountries)) ?>;
  var subtotal = <?= json_encode($subtotal) ?>;
  var freeThreshold = <?= json_encode(FREE_SHIPPING_THRESHOLD) ?>;

  var countrySelect = document.getElementById('countrySelect');
  var stateSelect = document.getElementById('stateSelect');
  var stateRow = document.getElementById('stateRow');
  var preselectedState = <?= json_encode($stateValue) ?>;

  var deliveryLabel = document.getElementById('deliveryLabel');
  var deliveryDays = document.getElementById('deliveryDays');
  var deliveryPrice = document.getElementById('deliveryPrice');
  var sumShipping = document.getElementById('sumShipping');
  var sumTotal = document.getElementById('sumTotal');

  function costForZone(zoneKey) {
    if (zoneKey === 'domestic' && subtotal >= freeThreshold) return 0;
    return zones[zoneKey] ? zones[zoneKey].price : 0;
  }

  function formatMoney(n) {
    return n > 0 ? '$' + Math.round(n) : 'Бесплатно';
  }

  function updateDeliveryInfo() {
    var zoneKey = zoneMap[countrySelect.value];
    if (!zoneKey || !zones[zoneKey]) {
      deliveryLabel.textContent = 'Выберите страну — способ доставки определится автоматически';
      deliveryDays.textContent = '';
      deliveryPrice.textContent = '';
      sumShipping.textContent = '—';
      sumTotal.textContent = formatMoney(subtotal);
      return;
    }
    var zone = zones[zoneKey];
    var cost = costForZone(zoneKey);
    deliveryLabel.textContent = zone.label;
    deliveryDays.textContent = zone.days;
    deliveryPrice.textContent = formatMoney(cost);
    sumShipping.textContent = formatMoney(cost);
    sumTotal.textContent = formatMoney(subtotal + cost);
  }

  function populateStates() {
    var country = countries.find(function (c) { return c.code === countrySelect.value; });
    stateSelect.innerHTML = '';
    if (!country || !country.states || !country.states.length) {
      stateRow.style.display = 'none';
      stateSelect.innerHTML = '<option value="">—</option>';
      return;
    }
    stateRow.style.display = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— выберите регион —';
    stateSelect.appendChild(placeholder);
    country.states.forEach(function (s) {
      var opt = document.createElement('option');
      opt.value = s.code;
      opt.textContent = s.name;
      if (s.code === preselectedState) opt.selected = true;
      stateSelect.appendChild(opt);
    });
  }

  if (countrySelect) {
    countrySelect.addEventListener('change', function () {
      populateStates();
      updateDeliveryInfo();
    });
    populateStates();
    updateDeliveryInfo();
  }
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
