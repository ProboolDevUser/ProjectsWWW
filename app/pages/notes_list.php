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

$where = "1=1";
$params = [];
if ($project_id > 0) { $where .= " AND n.project_id=?"; $params[] = $project_id; }

$sql = "SELECT n.*, p.title project_title
        FROM project_notes n
        JOIN projects p ON p.id=n.project_id
        WHERE $where
        ORDER BY n.note_date_utc DESC
        LIMIT 400";

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0">Notas</h1>
  <a class="btn pb-btn-gold btn-sm" href="index.php?p=note_edit"><i class="bi bi-plus-lg"></i> Nova Nota</a>
</div>

<div class="pb-card p-3 mb-3">
  <?= project_select($projects, $project_id, 'notes_list') ?>
</div>

<div class="pb-card p-2">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Título</th>
          <th class="d-none d-lg-table-cell">Projeto</th>
          <th>Data</th>
          <th style="width:140px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="4" class="pb-muted">Sem notas.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['title']) ?></td>
            <td class="d-none d-lg-table-cell pb-muted"><?= h($r['project_title']) ?></td>
            <td><?= toLisbon($r['note_date_utc']) ?></td>
            <td class="text-end">
              <a class="btn btn-outline-secondary btn-sm" href="index.php?p=note_edit&id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
              <a class="btn btn-outline-secondary btn-sm" href="index.php?p=note_edit&id=<?= (int)$r['id'] ?>&del=1&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar esta nota?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
