<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_role(['admin','consultor']);

require_once __DIR__ . '/../db.php';

$active = ($_GET['active'] ?? '1') === '1' ? 1 : 0;

$cols = function_exists('db_cols') ? db_cols('users') : [];
$has = fn(string $c) => in_array($c, $cols, true);

$fields = [
  'id','name','email','phone',
  ($has('title') ? 'title' : null),
  ($has('login') ? 'login' : null),
  ($has('web_url') ? 'web_url' : null),
  ($has('role') ? 'role' : null),
  ($has('is_active') ? 'is_active' : null),
];
$fields = array_values(array_filter($fields));

$st = db()->prepare('SELECT '.implode(',', $fields).' FROM users WHERE is_active=? ORDER BY name ASC');
$st->execute([$active]);
$rows = $st->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 mb-0">Utilizadores</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=users_list&active=1">Ativos</a>
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=users_list&active=0">Inativos</a>
    <a class="btn pb-btn-gold btn-sm" href="index.php?p=users_edit">Novo</a>
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
          <?php if ($has('title')): ?><th class="d-none d-lg-table-cell">Título</th><?php endif; ?>
          <?php if ($has('login')): ?><th class="d-none d-lg-table-cell">Login</th><?php endif; ?>
          <?php if ($has('web_url')): ?><th class="d-none d-xl-table-cell">Web</th><?php endif; ?>
          <?php if ($has('role')): ?><th class="d-none d-md-table-cell">Permissões</th><?php endif; ?>
          <th class="d-none d-md-table-cell">Estado</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['name']) ?></td>
            <td class="d-none d-md-table-cell"><?= h((string)($r['email'] ?? '')) ?></td>
            <td class="d-none d-md-table-cell"><?= h((string)($r['phone'] ?? '')) ?></td>
            <?php if ($has('title')): ?><td class="d-none d-lg-table-cell"><?= h((string)($r['title'] ?? '')) ?></td><?php endif; ?>
            <?php if ($has('login')): ?><td class="d-none d-lg-table-cell"><?= h((string)($r['login'] ?? '')) ?></td><?php endif; ?>
            <?php if ($has('web_url')): ?>
              <td class="d-none d-xl-table-cell">
                <?php if (!empty($r['web_url'])): ?>
                  <a class="link-light" href="<?= h((string)$r['web_url']) ?>" target="_blank" rel="noopener">Abrir</a>
                <?php endif; ?>
              </td>
            <?php endif; ?>
            <?php if ($has('role')): ?>
              <td class="d-none d-md-table-cell"><?= h(((function($v){$rn=role_norm((string)$v);return $rn==="admin"?"Administrador":($rn==="consultant"?"Consultor":($rn==="trainer"?"Formador":"Utilizador"));})($r['role'] ?? ""))) ?></td>
            <?php endif; ?>
            <td class="d-none d-md-table-cell"><?= ($r['is_active'] ?? 0) ? 'Ativo' : 'Inativo' ?></td>
            <td class="text-end">
              <a class="btn btn-outline-primary btn-sm" href="index.php?p=users_edit&id=<?= (int)$r['id'] ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
