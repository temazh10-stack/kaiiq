<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Контакты — Project Kai';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $message !== '') {
        // In a full production build this would send an email or store a support ticket.
        flash_set('Спасибо, ' . $name . '! Мы ответим вам в течение 24-48 часов.', 'success');
        header('Location: ' . url('contacts.php'));
        exit;
    }
    $sent = true;
}

require __DIR__ . '/includes/header.php';
?>
<div class="container section">
  <h1>Свяжитесь с нами</h1>
  <p class="subtitle" style="color:var(--color-text-muted); max-width:600px;">Вопросы о товаре? Хотите узнать больше о том, как мы отбираем вещи? Мы всегда на связи.</p>

  <div class="contact-grid" style="margin-top:40px;">
    <div>
      <h2>Контактная информация</h2>
      <div class="contact-info-row">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
        <div><strong>Email</strong><br><a href="mailto:hello@projectkai.com" style="color:var(--color-accent); text-decoration:underline;">hello@projectkai.com</a></div>
      </div>
      <div class="contact-info-row">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/></svg>
        <div><strong>Локация</strong><br>Портленд, Орегон<br>США</div>
      </div>
      <h3 style="margin-top:28px;">Мы в соцсетях</h3>
      <div class="social-row">
        <a href="#" class="social-circle" aria-label="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/></svg></a>
        <a href="#" class="social-circle" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 9h3V6h-3a3 3 0 0 0-3 3v2H8v3h3v7h3v-7h3l1-3h-4V9a1 1 0 0 1 1-1Z"/></svg></a>
      </div>

      <div class="notice-box" style="margin-top:24px;">
        <h3>Время ответа</h3>
        <p>Обычно мы отвечаем на все обращения в течение 24-48 часов. Если вопрос срочный и касается уже оформленного заказа, укажите номер заказа в теме сообщения.</p>
      </div>
    </div>

    <div>
      <h2>Отправить сообщение</h2>
      <form method="post" data-validate>
        <?= csrf_field() ?>
        <div class="field">
          <label>Имя</label>
          <input type="text" name="name" data-required value="<?= e($_POST['name'] ?? '') ?>">
          <?php if ($sent && trim($_POST['name'] ?? '') === ''): ?><div class="field-error">Укажите имя.</div><?php endif; ?>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" data-required value="<?= e($_POST['email'] ?? '') ?>">
          <?php if ($sent && !filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)): ?><div class="field-error">Введите корректный email-адрес.</div><?php endif; ?>
        </div>
        <div class="field">
          <label>Тема</label>
          <input type="text" name="subject" value="<?= e($_POST['subject'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Сообщение</label>
          <textarea name="message" data-required><?= e($_POST['message'] ?? '') ?></textarea>
          <?php if ($sent && trim($_POST['message'] ?? '') === ''): ?><div class="field-error">Напишите сообщение.</div><?php endif; ?>
        </div>
        <button type="submit" class="btn btn-dark btn-block">Отправить сообщение</button>
      </form>
    </div>
  </div>

  <hr style="border:none; border-top:1px solid var(--color-border); margin:48px 0;">

  <h2>Частые вопросы</h2>
  <div class="faq-grid">
    <div>
      <h3>Принимаете ли вы вещи от пользователей?</h3>
      <p>Да! Если у вас есть винтажные вещи 90-х — 2000-х в отличном состоянии, напишите нам на hello@projectkai.com с фото и описанием.</p>
    </div>
    <div>
      <h3>Можно ли посетить магазин?</h3>
      <p>Сейчас мы работаем только онлайн, но иногда проводим временные точки продаж в Портленде. Следите за анонсами в Instagram.</p>
    </div>
    <div>
      <h3>Как вы проверяете подлинность винтажных вещей?</h3>
      <p>Каждая вещь проверяется по биркам, лейблам, деталям кроя и составу ткани. У нас более 10 лет опыта в определении подлинности винтажа.</p>
    </div>
    <div>
      <h3>Работаете ли вы оптом?</h3>
      <p>Мы сотрудничаем с отдельными бутиками и стилистами. По вопросам оптовых поставок напишите нам с подробностями о вашем бизнесе.</p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
