<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

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

$type = trim($_GET['type'] ?? '');
$q = trim($_GET['q'] ?? '');
$from_local = trim($_GET['from'] ?? '');
$to_local = trim($_GET['to'] ?? '');

$tz = new DateTimeZone('Europe/Lisbon');
$from_utc = null;
$to_utc = null;

if ($from_local !== '') {
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $from_local, $tz);
  if ($d) $from_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d 00:00:00');
}
if ($to_local !== '') {
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $to_local, $tz);
  if ($d) $to_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d 23:59:59');
}

$whereParts = [];
$params = [];

if ($project_id > 0) { $whereParts[] = "x.project_id=?"; $params[] = $project_id; }
if ($type !== '') { $whereParts[] = "x.item_type=?"; $params[] = $type; }
if ($q !== '') { $whereParts[] = "x.item_title LIKE ?"; $params[] = '%' . $q . '%'; }
if ($from_utc) { $whereParts[] = "x.item_date_utc >= ?"; $params[] = $from_utc; }
if ($to_utc) { $whereParts[] = "x.item_date_utc <= ?"; $params[] = $to_utc; }

$where = $whereParts ? ("WHERE " . implode(" AND ", $whereParts)) : "";

$union = "
  SELECT m.id AS item_id, m.project_id, 'Reunião' AS item_type, m.title AS item_title, m.starts_at_utc AS item_date_utc,
         NULL AS item_status, NULL AS item_extra, p.title AS project_title
  FROM project_meetings m JOIN projects p ON p.id=m.project_id

  UNION ALL

  SELECT n.id AS item_id, n.project_id, 'Nota' AS item_type, n.title AS item_title, n.note_date_utc AS item_date_utc,
         NULL AS item_status, NULL AS item_extra, p.title AS project_title
  FROM project_notes n JOIN projects p ON p.id=n.project_id

  UNION ALL

  SELECT t.id AS item_id, t.project_id, 'Tarefa' AS item_type, t.title AS item_title,
         COALESCE(t.due_date_utc, t.start_date_utc, t.created_at_utc) AS item_date_utc,
         t.status AS item_status, t.priority AS item_extra, p.title AS project_title
  FROM project_tasks t JOIN projects p ON p.id=t.project_id

  UNION ALL

  SELECT f.id AS item_id, f.project_id, 'Ficheiro' AS item_type, f.original_name AS item_title, f.uploaded_at_utc AS item_date_utc,
         NULL AS item_status, f.relative_path AS item_extra, p.title AS project_title
  FROM project_files f JOIN projects p ON p.id=f.project_id
";

$sql = "SELECT * FROM ( $union ) x
        $where
        ORDER BY x.item_date_utc DESC
        LIMIT 600";

$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$types = ['Reunião','Nota','Tarefa','Ficheiro'];
?>

<div class="pb-page-title mb-3 d-flex align-items-center justify-content-between">
  <h1 class="h5 m-0">Tudo</h1>
  <a class="btn pb-btn-gold btn-sm" href="index.php?p=task_edit"><i class="bi bi-plus-lg"></i> Novo</a>
</div>

<form method="get" class="pb-filterbar mb-3">
  <input type="hidden" name="p" value="items_all">

  <div class="pb-pill">
    <i class="bi bi-diagram-3"></i>
    <select name="project_id">
      <option value="0">Projeto: Todos</option>
      <?php foreach ($projects as $pr): ?>
        <option value="<?= (int)$pr['id'] ?>" <?= ($project_id===(int)$pr['id'])?'selected':'' ?>>Projeto: <?= h($pr['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="pb-pill">
    <i class="bi bi-tags"></i>
    <select name="type">
      <option value="">Tipo: Todos</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= h($t) ?>" <?= ($type===$t)?'selected':'' ?>>Tipo: <?= h($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="pb-pill">
    <i class="bi bi-search"></i>
    <input name="q" value="<?= h($q) ?>" placeholder="Pesquisar...">
  </div>

  <div class="pb-pill">
    <i class="bi bi-calendar3"></i>
    <input type="date" name="from" value="<?= h($from_local) ?>">
  </div>
  <div class="pb-pill">
    <i class="bi bi-calendar3"></i>
    <input type="date" name="to" value="<?= h($to_local) ?>">
  </div>

  <button class="btn btn-sm pb-btn-outline" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
</form>

<div class="pb-grid">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Título</th>
          <th style="width:150px;">Tipo</th>
          <th class="d-none d-lg-table-cell">Projeto</th>
          <th style="width:140px;">Data</th>
          <th class="d-none d-md-table-cell" style="width:140px;">Estado</th>
          <th style="width:110px;" class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="6" class="pb-muted">Sem itens.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $editUrl = '#';
            $icoCls = 'task';
            $ico = 'bi-check2-square';

            if ($r['item_type']==='Reunião') { $editUrl = 'index.php?p=meeting_edit&id='.(int)$r['item_id']; $icoCls='meeting'; $ico='bi-calendar-event'; }
            elseif ($r['item_type']==='Nota') { $editUrl = 'index.php?p=note_edit&id='.(int)$r['item_id']; $icoCls='note'; $ico='bi-journal-text'; }
            elseif ($r['item_type']==='Tarefa') { $editUrl = 'index.php?p=task_edit&id='.(int)$r['item_id']; $icoCls='task'; $ico='bi-check2-square'; }
            elseif ($r['item_type']==='Ficheiro') { $editUrl = 'index.php?p=files&project_id='.(int)$r['project_id']; $icoCls='file'; $ico='bi-folder2-open'; }
          ?>
          <tr>
            <td>
              <div class="pb-row-title">
                <span class="pb-ico <?= h($icoCls) ?>"><i class="bi <?= h($ico) ?>"></i></span>
                <div>
                  <div class="fw-semibold"><?= h($r['item_title']) ?></div>
                  <div class="pb-muted small d-none d-md-block"><?= h($r['project_title']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="pb-chip"><?= h($r['item_type']) ?></span></td>
            <td class="d-none d-lg-table-cell pb-muted"><?= h($r['project_title']) ?></td>
            <td><?= toLisbon($r['item_date_utc']) ?></td>
            <td class="d-none d-md-table-cell"><?= $r['item_status'] ? h($r['item_status']) : '<span class="pb-muted">-</span>' ?></td>
            <td class="text-end">
              <div class="pb-actions">
                <?php if ($r['item_type']==='Ficheiro' && !empty($r['item_extra'])): ?>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= h($r['item_extra']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= h($editUrl) ?>"><i class="bi bi-pencil"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

