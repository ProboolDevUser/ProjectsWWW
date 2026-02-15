<?php
require_context();
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_role(['admin','consultor']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$client = ['name'=>'','email'=>'','phone'=>'','is_active'=>1];

if ($id) {
  $st = db()->prepare("SELECT * FROM clients WHERE id=?");
  $st->execute([$id]);
  $client = $st->fetch();
  if (!$client) { echo "Cliente não encontrado."; exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  if ($id) {
    $st = db()->prepare("UPDATE clients SET name=?, email=?, phone=?, is_active=? WHERE id=?");
    $st->execute([$name, $email ?: null, $phone ?: null, $is_active, $id]);
  } else {
    $st = db()->prepare("INSERT INTO clients(name,email,phone,is_active) VALUES (?,?,?,?)");
    $st->execute([$name, $email ?: null, $phone ?: null, $is_active]);
    $id = (int)db()->lastInsertId();
  }
  redirect('index.php?p=clients_list');
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 mb-0"><?= $id ? 'Editar cliente' : 'Novo cliente' ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="index.php?p=clients_list">Voltar</a>
</div>

<div class="pb-card p-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div class="col-12">
      <label class="form-label">Nome</label>
      <input class="form-control" name="name" required value="<?= h($client['name']) ?>">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Email</label>
      <input class="form-control" name="email" value="<?= h((string)$client['email']) ?>">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Telefone</label>
      <input class="form-control" name="phone" value="<?= h((string)$client['phone']) ?>">
    </div>

    <div class="col-12 mt-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= ((int)$client['is_active']===1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Ativo</label>
      </div>
    </div>

    <div class="col-12 mt-3">
      <button class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>