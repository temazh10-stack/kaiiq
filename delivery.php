<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Доставка — Project Kai';
require __DIR__ . '/includes/header.php';

$zones = delivery_zones();
?>
<div class="container section">
  <h1>Доставка</h1>
  <p class="subtitle" style="color:var(--color-text-muted); max-width:640px;">Мы бережно упаковываем и отправляем каждую винтажную вещь, чтобы она доехала в целости. Все заказы отправляются с трек-номером.</p>

  <h2 style="margin-top:36px;"><?= icon('truck', 24) ?> Зоны и сроки доставки</h2>
  <?php foreach ($zones as $z): ?>
    <div class="zone-row">
      <div>
        <div style="font-weight:600;"><?= e($z['label']) ?></div>
        <div class="zone-days"><?= e($z['days']) ?></div>
      </div>
      <div class="zone-price">$<?= e($z['price']) ?> фикс.</div>
    </div>
  <?php endforeach; ?>

  <div class="callout"><strong>Бесплатная доставка</strong> «По России» при заказе от $<?= FREE_SHIPPING_THRESHOLD ?>. Способ доставки выбирается в <a href="<?= url('cart.php') ?>" style="color:var(--color-accent);">корзине</a> при оформлении заказа.</div>

  <h2 style="margin-top:48px;"><?= icon('package', 24) ?> Упаковка</h2>
  <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:24px;" class="package-grid">
    <div>
      <p>Каждая вещь бережно оборачивается бескислотной папиросной бумагой, чтобы сохранить структуру ткани. Мы используем переработанные картонные коробки и биоразлагаемые пакеты, где это возможно.</p>
      <p>Деликатные вещи — шёлк, кружево — дополнительно защищаются слоями упаковки. Тяжёлые вещи вроде джинсовых курток и пальто упаковываются так, чтобы не сдвигались при перевозке.</p>
    </div>
    <div class="dark-panel" style="padding:22px;">
      <h4><?= icon('leaf', 20) ?> Экологичный подход</h4>
      <p style="font-size:14px;">Покупка винтажа — уже осознанный выбор. Мы продолжаем эту идею, используя экологичные упаковочные материалы и сводя отходы к минимуму. Вся наша упаковка перерабатывается или компостируется.</p>
    </div>
  </div>

  <div class="notice-box" style="margin-top:40px;">
    <h2>Возврат и обмен</h2>
    <p>Мы хотим, чтобы вам понравилась ваша винтажная находка. Если вещь не подошла или оказалась не тем, что вы ожидали, мы принимаем возврат в течение <strong>14 дней</strong> с момента доставки.</p>
    <ul style="list-style:disc; margin-left:20px;">
      <li>Вещь должна быть не ношена и в том же состоянии, в каком была получена</li>
      <li>Оригинальные бирки (если были) должны остаться на месте</li>
      <li>Стоимость обратной доставки оплачивает покупатель</li>
      <li>Возврат средств оформляется в течение 5-7 рабочих дней после получения вещи</li>
      <li>Товары с пометкой «Final Sale» возврату не подлежат</li>
    </ul>
    <p style="margin-top:12px;">Остались вопросы по заказу? Напишите нам: <a href="mailto:hello@projectkai.com" style="color:var(--color-accent); text-decoration:underline;">hello@projectkai.com</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
