<?php
declare(strict_types=1);
/*
  Ferramenta temporária para criar um utilizador.
  Usa uma vez, cria o user, e depois APAGA este ficheiro.

  Aceder:
  http://localhost/probool-projects/public/tools/make_user.php
*/
require_once __DIR__ . '/../../app/db.php';

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $full_name = trim($_POST['full_name'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  if ($username === '' || $full_name === '' || $password === '') {
    $msg = "Preenche tudo.";
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = db()->prepare("INSERT INTO users(username, full_name, password_hash, is_active) VALUES (?,?,?,1)");
    $st->execute([$username, $full_name, $hash]);
    $msg = "Utilizador criado. Agora apaga este ficheiro!";
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Criar utilizador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">
  <div class="container" style="max-width:520px">
    <h1 class="h5 mb-3">Criar utilizador (temporário)</h1>

    <?php if ($msg): ?>
      <div class="alert alert-info"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="vstack gap-2">
      <div>
        <label class="form-label">Username</label>
        <input class="form-control" name="username" required>
      </div>
      <div>
        <label class="form-label">Nome completo</label>
        <input class="form-control" name="full_name" required>
      </div>
      <div>
        <label class="form-label">Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary mt-2">Criar</button>
    </form>

    <div class="text-danger small mt-3">
      Depois de criares o utilizador, apaga este ficheiro: <b>/public/tools/make_user.php</b>
    </div>
  </div>
</body>
</html>
