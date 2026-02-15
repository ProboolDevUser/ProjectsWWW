<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_role(['admin','consultor']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$active = ($_GET['active'] ?? '1') === '1' ? 1 : 0;

$sql = "SELECT p.*, c.name AS client_name
        FROM projects p
        JOIN clients c ON c.id = p.client_id
        WHERE p.is_active = ?
        ORDER BY p.created_at_utc DESC";
$st = db()->prepare($sql);
$st->execute([$active]);
$projects = $st->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 mb-0">Projetos</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=projects_list&active=1">Ativos</a>
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=projects_list&active=0">Inativos</a>
    <a class="btn pb-btn-gold btn-sm" href="index.php?p=projects_edit">Novo</a>
  </div>
</div>

<div class="row g-3">
  <?php foreach ($projects as $p): ?>
    <div class="col-12 col-md-6 col-lg-4">
      <a class="text-decoration-none" href="index.php?p=project_view&id=<?= (int)$p['id'] ?>">
        <div class="pb-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold"><?= h($p['title']) ?></div>
              <div class="pb-muted small"><?= h($p['client_name']) ?><?= $p['code'] ? ' • ' . h($p['code']) : '' ?></div>
            </div>
            <span class="badge" style="background:<?= h($p['color_tag'] ?: '#2D6CDF') ?>;">&nbsp;</span>
          </div>

          <hr>

          <div class="small">
            <div class="pb-muted">Início</div>
            <div><?= toLisbon($p['start_date_utc']) ?></div>
            <div class="pb-muted mt-2">Prazo</div>
            <div><?= toLisbon($p['due_date_utc']) ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>