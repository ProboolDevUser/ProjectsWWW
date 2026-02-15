<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_role(['admin','consultor']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$active = ($_GET['active'] ?? '1') === '1' ? 1 : 0;

$st = db()->prepare("SELECT * FROM clients WHERE is_active=? ORDER BY name ASC");
$st->execute([$active]);
$rows = $st->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 mb-0">Clientes</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=clients_list&active=1">Ativos</a>
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=clients_list&active=0">Inativos</a>
    <a class="btn pb-btn-gold btn-sm" href="index.php?p=clients_edit">Novo</a>
  </div>
</div>

<div class="pb-card p-2">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th class="d-none d-md-table-cell">Email</th>
          <th class="d-none d-md-table-cell">Telefone</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td class="d-none d-md-table-cell"><?= h((string)$r['email']) ?></td>
            <td class="d-none d-md-table-cell"><?= h((string)$r['phone']) ?></td>
            <td class="text-end">
              <a class="btn btn-outline-primary btn-sm" href="index.php?p=clients_edit&id=<?= (int)$r['id'] ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>