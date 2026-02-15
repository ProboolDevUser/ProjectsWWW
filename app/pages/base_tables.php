<?php
require_context();
require_login();

$tables = [
  [
    'title' => 'Clientes',
    'desc'  => 'Gestão e consulta de clientes.',
    'icon'  => 'bi-building',
    'view'  => 'clients_list',
    'edit'  => 'clients_list' // por agora vai para a grelha; mais tarde criamos editor dedicado
  ],
  [
    'title' => 'Projetos',
    'desc'  => 'Gestão e consulta de projetos.',
    'icon'  => 'bi-grid-1x2',
    'view'  => 'projects_list',
    'edit'  => 'projects_list'
  ],
  [
    'title' => 'Utilizadores',
    'desc'  => 'Perfis e acessos (Admin/Consultor/Formador).',
    'icon'  => 'bi-person-badge',
    'view'  => 'users_list',
    'edit'  => 'users_list'
  ],
];
?>

<div class="pb-page-title mb-3 d-flex align-items-center justify-content-between">
  <h1 class="h5 m-0">Tabelas base</h1>
</div>

<div class="row g-3">
  <?php foreach ($tables as $t): ?>
    <div class="col-12 col-lg-4">
      <div class="pb-card p-3 h-100">
        <div class="d-flex align-items-start justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="pb-pill-icon"><i class="bi <?= h($t['icon']) ?>"></i></div>
            <div>
              <div class="fw-semibold"><?= h($t['title']) ?></div>
              <div class="pb-muted small mt-1"><?= h($t['desc']) ?></div>
            </div>
          </div>
          <a class="btn btn-sm pb-btn-outline" href="index.php?p=<?= h($t['edit']) ?>" title="Editar">
            <i class="bi bi-pencil"></i>
          </a>
        </div>

        <div class="mt-3 d-flex gap-2">
          <a class="btn btn-sm pb-btn-gold" href="index.php?p=<?= h($t['view']) ?>">
            <i class="bi bi-eye"></i> Abrir
          </a>
          <a class="btn btn-sm pb-btn-outline" href="index.php?p=<?= h($t['edit']) ?>">
            <i class="bi bi-pencil"></i> Editar
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
