<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . url('cabinet/index.php'));
    exit;
}

$errors = [];
$values = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'city' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) {
        $values[$key] = trim($_POST[$key] ?? '');
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($values['first_name'] === '') $errors['first_name'] = 'Укажите имя.';
    if ($values['last_name'] === '')  $errors['last_name'] = 'Укажите фамилию.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email-адрес.';
    }
    if ($values['phone'] !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $values['phone'])) {
        $errors['phone'] = 'Введите корректный номер телефона.';
    }
    if ($values['city'] === '') $errors['city'] = 'Укажите город.';
    if (strlen($password) < 8) $errors['password'] = 'Пароль должен быть не короче 8 символов.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Пароли не совпадают.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT 1 FROM user WHERE email = ?');
        $check->execute([$values['email']]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'Аккаунт с таким email уже существует.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO user (email, password_hash, first_name, last_name, phone, city, role) VALUES (?, ?, ?, ?, ?, ?, "customer")'
        );
        $stmt->execute([
            $values['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $values['first_name'],
            $values['last_name'],
            $values['phone'] ?: null,
            $values['city'],
        ]);

        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        $_SESSION['role'] = 'customer';
        $_SESSION['name'] = $values['first_name'];

        flash_set('Добро пожаловать в Project Kai, ' . $values['first_name'] . '!', 'success');
        header('Location: ' . url('cabinet/index.php'));
        exit;
    }
}

$pageTitle = 'Регистрация — Project Kai';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:480px;">
    <h1>Создать аккаунт</h1>
    <p class="subtitle">Присоединяйтесь к Project Kai, чтобы сохранять избранное и отслеживать заказы</p>

    <form method="post" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="form-row-split">
        <div class="field <?= isset($errors['first_name']) ? 'has-error' : '' ?>">
          <label>Имя</label>
          <input type="text" name="first_name" data-required value="<?= e($values['first_name']) ?>">
          <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['last_name']) ? 'has-error' : '' ?>">
          <label>Фамилия</label>
          <input type="text" name="last_name" data-required value="<?= e($values['last_name']) ?>">
          <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
        <label>Email</label>
        <input type="email" name="email" data-required placeholder="your@email.com" value="<?= e($values['email']) ?>">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>

      <div class="form-row-split">
        <div class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
          <label>Телефон</label>
          <input type="tel" name="phone" data-mask="phone" placeholder="+7 900 123 45 67" value="<?= e($values['phone']) ?>">
          <?php if (isset($errors['phone'])): ?><div class="field-error"><?= e($errors['phone']) ?></div><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['city']) ? 'has-error' : '' ?>">
          <label>Город</label>
          <input type="text" name="city" data-required value="<?= e($values['city']) ?>">
          <?php if (isset($errors['city'])): ?><div class="field-error"><?= e($errors['city']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
        <label>Пароль</label>
        <input type="password" name="password" data-required data-min-length="8" placeholder="Не менее 8 символов">
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
      <div class="field <?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
        <label>Подтвердите пароль</label>
        <input type="password" name="confirm_password" data-required data-match="password" placeholder="Введите пароль ещё раз">
        <?php if (isset($errors['confirm_password'])): ?><div class="field-error"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
      </div>

      <button type="submit" class="btn btn-dark btn-block">Создать аккаунт</button>
      <div class="auth-foot">Уже есть аккаунт? <a href="<?= url('login.php') ?>" style="color:var(--color-accent); text-decoration:underline;">Войти</a></div>
    </form>
  </div>
</div>
<p class="auth-legal">Создавая аккаунт, вы соглашаетесь с Условиями использования и Политикой конфиденциальности</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
