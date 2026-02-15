<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = (int)($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'overview';

$st = db()->prepare("SELECT p.*, c.name client_name FROM projects p JOIN clients c ON c.id=p.client_id WHERE p.id=?");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { echo "Projeto não encontrado."; exit; }

function tabLink(int $id, string $tab, string $cur, string $label, string $icon): string {
  $active = ($tab===$cur) ? 'active' : '';
  return '<a class="nav-link '.$active.'" href="index.php?p=project_view&id='.$id.'&tab='.$tab.'"><i class="bi '.$icon.'"></i> '.$label.'</a>';
}
?>

<div class="pb-page-title mb-3">
  <div>
    <h1 class="h5 m-0"><?= h($p['title']) ?></h1>
    <div class="pb-muted small"><?= h($p['client_name']) ?><?= $p['code'] ? ' • ' . h($p['code']) : '' ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=projects_edit&id=<?= (int)$p['id'] ?>"><i class="bi bi-pencil"></i></a>
    <a class="btn btn-outline-secondary btn-sm" href="index.php?p=projects_list"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>

<div class="pb-card p-3 mb-3">
  <div class="d-flex flex-wrap gap-2">
    <span class="pb-chip"><i class="bi bi-calendar3"></i> Início: <?= toLisbon($p['start_date_utc']) ?></span>
    <span class="pb-chip"><i class="bi bi-flag"></i> Prazo: <?= toLisbon($p['due_date_utc']) ?></span>
    <span class="pb-chip"><i class="bi bi-circle-fill" style="color:<?= h($p['color_tag'] ?: '#F5A623') ?>;"></i> Cor</span>
  </div>

  <div class="pb-divider"></div>

  <div class="nav nav-pills gap-2">
    <?= tabLink((int)$p['id'], 'overview', $tab, 'Resumo', 'bi-grid') ?>
    <?= tabLink((int)$p['id'], 'meetings', $tab, 'Reuniões', 'bi-calendar-event') ?>
    <?= tabLink((int)$p['id'], 'notes', $tab, 'Notas', 'bi-journal-text') ?>
    <?= tabLink((int)$p['id'], 'tasks', $tab, 'Tarefas', 'bi-check2-square') ?>
    <?= tabLink((int)$p['id'], 'files', $tab, 'Ficheiros', 'bi-folder2-open') ?>
    <?= tabLink((int)$p['id'], 'all', $tab, 'Tudo', 'bi-list-check') ?>
  </div>
</div>

<?php if ($tab === 'overview'): ?>
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="pb-card p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Próximas reuniões</div>
          <a class="btn btn-outline-secondary btn-sm" href="index.php?p=meeting_edit&project_id=<?= (int)$p['id'] ?>"><i class="bi bi-plus-lg"></i></a>
        </div>
        <div class="pb-divider"></div>
        <?php
          $st = db()->prepare("SELECT title, starts_at_utc FROM project_meetings WHERE project_id=? AND starts_at_utc >= UTC_TIMESTAMP() ORDER BY starts_at_utc ASC LIMIT 5");
          $st->execute([(int)$p['id']]);
          $rows = $st->fetchAll();
        ?>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Título</th><th>Início</th></tr></thead>
            <tbody>
              <?php if (!$rows): ?><tr><td colspan="2" class="pb-muted">Sem reuniões futuras.</td></tr><?php endif; ?>
              <?php foreach ($rows as $r): ?><tr><td><?= h($r['title']) ?></td><td><?= toLisbon($r['starts_at_utc']) ?></td></tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="pb-card p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Tarefas por concluir</div>
          <a class="btn btn-outline-secondary btn-sm" href="index.php?p=task_edit&project_id=<?= (int)$p['id'] ?>"><i class="bi bi-plus-lg"></i></a>
        </div>
        <div class="pb-divider"></div>
        <?php
          $st = db()->prepare("SELECT title, due_date_utc, status FROM project_tasks WHERE project_id=? AND status <> 'Concluída' ORDER BY (due_date_utc IS NULL), due_date_utc ASC LIMIT 8");
          $st->execute([(int)$p['id']]);
          $rows = $st->fetchAll();
        ?>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Título</th><th>Prazo</th><th>Estado</th></tr></thead>
            <tbody>
              <?php if (!$rows): ?><tr><td colspan="3" class="pb-muted">Sem tarefas.</td></tr><?php endif; ?>
              <?php foreach ($rows as $r): ?><tr><td><?= h($r['title']) ?></td><td><?= toLisbon($r['due_date_utc']) ?></td><td class="pb-muted"><?= h($r['status']) ?></td></tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'meetings'): ?>
  <?php redirect('index.php?p=meetings_list&project_id='.(int)$p['id']); ?>
<?php elseif ($tab === 'notes'): ?>
  <?php redirect('index.php?p=notes_list&project_id='.(int)$p['id']); ?>
<?php elseif ($tab === 'tasks'): ?>
  <?php redirect('index.php?p=tasks_list&project_id='.(int)$p['id']); ?>
<?php elseif ($tab === 'files'): ?>
  <?php redirect('index.php?p=files&project_id='.(int)$p['id']); ?>
<?php elseif ($tab === 'all'): ?>
  <?php redirect('index.php?p=items_all&project_id='.(int)$p['id']); ?>
<?php endif; ?>
