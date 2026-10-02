<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (is_admin() ? url('admin/index.php') : url('cabinet/index.php')));
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email-адрес.';
    }
    if ($password === '') {
        $errors['password'] = 'Введите пароль.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM user WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors['password'] = 'Неверный email или пароль.';
        } else {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'];

            if (!empty($_POST['remember'])) {
                setcookie('pk_remember_email', $email, time() + 60 * 60 * 24 * 30, '/');
            }

            header('Location: ' . ($user['role'] === 'admin' ? url('admin/index.php') : url('cabinet/index.php')));
            exit;
        }
    }
}

if ($email === '' && !empty($_COOKIE['pk_remember_email'])) {
    $email = $_COOKIE['pk_remember_email'];
}

$pageTitle = 'Вход — Project Kai';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>С возвращением</h1>
    <p class="subtitle">Войдите в свой аккаунт</p>

    <form method="post" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
        <label>Email</label>
        <input type="email" name="email" data-required placeholder="your@email.com" value="<?= e($email) ?>">
        <?php if (isset($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
        <label>Пароль</label>
        <input type="password" name="password" data-required placeholder="••••••••">
        <?php if (isset($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <label class="checkbox-row"><input type="checkbox" name="remember"> Запомнить меня</label>
        <a href="#" style="font-size:13px; color:var(--color-accent); text-decoration:underline;">Забыли пароль?</a>
      </div>
      <button type="submit" class="btn btn-dark btn-block">Войти</button>
      <div class="auth-foot">Нет аккаунта? <a href="<?= url('register.php') ?>" style="color:var(--color-accent); text-decoration:underline;">Создать</a></div>
    </form>
  </div>
</div>
<p class="auth-legal">Входя в аккаунт, вы соглашаетесь с Условиями использования и Политикой конфиденциальности</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
