<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_context();

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$projects = db()->query("SELECT id, title FROM projects WHERE is_active=1 ORDER BY title ASC")->fetchAll();

function project_select(array $projects, int $project_id, string $page): string {
  ob_start(); ?>
  <form method="get" class="row g-2 align-items-end">
    <input type="hidden" name="p" value="<?= h($page) ?>">
    <div class="col-12 col-md-6 col-lg-5">
      <label class="form-label">Projeto</label>
      <select class="form-select" name="project_id">
        <option value="0">Todos</option>
        <?php foreach ($projects as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= ($project_id===(int)$pr['id'])?'selected':'' ?>>
            <?= h($pr['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-3 col-lg-2">
      <button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
    </div>
  </form>
  <?php return ob_get_clean();
}

$status = trim($_GET['status'] ?? '');
$where = "1=1";
$params = [];
if ($project_id > 0) { $where .= " AND t.project_id=?"; $params[] = $project_id; }
if ($status !== '') { $where .= " AND t.status=?"; $params[] = $status; }

$sql = "SELECT t.*, p.title project_title
        FROM project_tasks t
        JOIN projects p ON p.id=t.project_id
        WHERE $where
        ORDER BY (t.due_date_utc IS NULL), t.due_date_utc ASC, t.id DESC
        LIMIT 500";
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$statuses = ['Aberta','Em curso','Bloqueada','Concluída'];
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0">Tarefas</h1>
  <a class="btn pb-btn-gold btn-sm" href="index.php?p=task_edit"><i class="bi bi-plus-lg"></i> Nova Tarefa</a>
</div>

<div class="pb-card p-3 mb-3">
  <?= project_select($projects, $project_id, 'tasks_list') ?>

  <div class="mt-3">
    <div class="nav nav-pills gap-2">
      <a class="nav-link <?= $status===''?'active':'' ?>" href="index.php?p=tasks_list&project_id=<?= $project_id ?>">Todas</a>
      <?php foreach ($statuses as $s): ?>
        <a class="nav-link <?= $status===$s?'active':'' ?>" href="index.php?p=tasks_list&project_id=<?= $project_id ?>&status=<?= urlencode($s) ?>"><?= h($s) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="pb-card p-2">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Título</th>
          <th class="d-none d-lg-table-cell">Projeto</th>
          <th>Prazo</th>
          <th>Estado</th>
          <th>Prioridade</th>
          <th style="width:140px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="pb-muted">Sem tarefas.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $badge = 'good';
            if ($r['status'] !== 'Concluída' && $r['due_date_utc'] && $r['due_date_utc'] < gmdate('Y-m-d H:i:s')) $badge = 'bad';
            elseif ($r['status'] !== 'Concluída' && $r['due_date_utc']) $badge = 'warn';
          ?>
          <tr>
            <td><?= h($r['title']) ?></td>
            <td class="d-none d-lg-table-cell pb-muted"><?= h($r['project_title']) ?></td>
            <td><?= toLisbon($r['due_date_utc']) ?></td>
            <td><span class="pb-badge <?= $badge ?>"><?= h($r['status']) ?></span></td>
            <td><?= h($r['priority']) ?></td>
            <td class="text-end">
              <a class="btn btn-outline-secondary btn-sm" href="index.php?p=task_edit&id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
              <a class="btn btn-outline-secondary btn-sm" href="index.php?p=task_edit&id=<?= (int)$r['id'] ?>&del=1&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar esta tarefa?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
