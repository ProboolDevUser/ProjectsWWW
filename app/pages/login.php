<?php
// Página de login (sem layout)

if (current_user()) {
  redirect('index.php?p=dashboard');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if (auth_login($username, $password)) {
    redirect('index.php?p=dashboard');
  }

  $error = 'Credenciais inválidas.';
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="pb-login-bg">
  <div class="pb-login-card">
    <div class="pb-login-logo">
      <img class="pb-login-logo-img" src="/assets/img/ProboolProjectHub.png" alt="ProBool Project Hub">
    </div>

    <div class="pb-login-title">Acesso à Plataforma</div>

    <?php if ($error): ?>
      <div class="pb-alert pb-alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="pb-login-form" autocomplete="on">
      <?= csrf_field() ?>

      <div class="pb-field">
        <label class="pb-label" for="username">Utilizador</label>
        <input class="pb-input" id="username" type="text" name="username" value="<?= e($username ?? '') ?>" autocomplete="username" autofocus required>
      </div>

      <div class="pb-field">
        <label class="pb-label" for="password">Palavra-passe</label>
        <input class="pb-input" id="password" type="password" name="password" autocomplete="current-password" required>
      </div>

      <div class="pb-login-actions">
        <button class="pb-btn-gold" type="submit">Entrar</button>
      </div>
    </form>

    <div class="pb-login-footer">ProBool Project Hub</div>
  </div>
</body>
</html>
