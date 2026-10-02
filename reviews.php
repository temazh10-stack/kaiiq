<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Отзывы — Project Kai';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    verify_csrf();
    require_login();

    $productId = (int) ($_POST['product_id'] ?? 0);
    $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');

    if ($productId && $comment !== '') {
        $stmt = $pdo->prepare('INSERT INTO reviews (id_user, id_product, rating, comment) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $productId, $rating, $comment]);
        flash_set('Спасибо, что поделились своей историей!', 'success');
    } else {
        flash_set('Пожалуйста, выберите товар и напишите комментарий.', 'error');
    }
    header('Location: ' . url('reviews.php'));
    exit;
}

$reviews = $pdo->query(
    'SELECT r.*, u.first_name, u.last_name, u.city, p.name AS product_name
     FROM reviews r
     JOIN user u ON u.id_user = r.id_user
     JOIN products p ON p.id_product = r.id_product
     ORDER BY r.created_at DESC'
)->fetchAll();

$total = count($reviews);
$avg = $total ? array_sum(array_column($reviews, 'rating')) / $total : 0;
$counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) {
    $counts[(int) $r['rating']]++;
}

$myProducts = [];
if (is_logged_in()) {
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.id_product, p.name FROM order_items oi
         JOIN orders o ON o.id_order = oi.id_order
         JOIN products p ON p.id_product = oi.id_product
         WHERE o.id_user = ? ORDER BY p.name'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $myProducts = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1>Отзывы</h1>
  <p class="subtitle" style="color:var(--color-text-muted);">Что говорят наши покупатели о своих винтажных находках</p>

  <div class="rating-summary" style="margin-top:24px;">
    <div class="rating-score">
      <div class="score"><?= number_format($avg, 1) ?></div>
      <div class="stars"><?= star_rating($avg) ?></div>
      <div style="font-size:13px; color:var(--color-text-muted);">На основе <?= $total ?> отзыв<?= plural_ru($total, 'а', 'ов', 'ов') ?></div>
    </div>
    <div class="rating-bars">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <?php $pct = $total ? ($counts[$i] / $total) * 100 : 0; ?>
        <div class="rating-bar-row">
          <span><?= $i ?> <?= icon('star-filled', 13) ?></span>
          <div class="rating-bar-track"><div class="rating-bar-fill" style="width: <?= $pct ?>%;"></div></div>
          <span><?= $counts[$i] ?></span>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <div style="margin-top:32px;">
    <?php foreach ($reviews as $r): ?>
      <div class="review-card">
        <div style="flex:1;">
          <div class="review-card__head">
            <div>
              <div class="review-card__name"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></div>
              <div class="review-card__location"><?= e($r['city']) ?> &middot; <?= e($r['product_name']) ?></div>
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
  </div>

  <div class="dark-panel text-center" style="margin-top:20px;">
    <h2>Поделитесь своей историей</h2>
    <p>Нам важно узнать о вашем опыте с винтажной находкой. Ваш отзыв помогает другим покупателям сделать осознанный выбор.</p>

    <?php if (!is_logged_in()): ?>
      <a href="<?= url('login.php') ?>" class="btn btn-outline-light">Войти, чтобы написать отзыв</a>
    <?php elseif (!$myProducts): ?>
      <p style="font-size:13px;">Вы сможете написать отзыв после покупки товара.</p>
    <?php else: ?>
      <form method="post" data-validate style="max-width:480px; margin:20px auto 0; text-align:left;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="submit_review">
        <div class="field">
          <label style="color:#fff;">Товар</label>
          <select name="product_id" data-required>
            <?php foreach ($myProducts as $mp): ?>
              <option value="<?= (int) $mp['id_product'] ?>"><?= e($mp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label style="color:#fff;">Оценка</label>
          <select name="rating">
            <option value="5">5 — Отлично</option>
            <option value="4">4 — Хорошо</option>
            <option value="3">3 — Средне</option>
            <option value="2">2 — Плохо</option>
            <option value="1">1 — Ужасно</option>
          </select>
        </div>
        <div class="field">
          <label style="color:#fff;">Ваш отзыв</label>
          <textarea name="comment" data-required placeholder="Расскажите о своей винтажной находке..."></textarea>
        </div>
        <button type="submit" class="btn btn-outline-light btn-block">Написать отзыв</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
