<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$projects = db()->query("SELECT id, title FROM projects WHERE is_active=1 ORDER BY title ASC")->fetchAll();
if (!$projects) { echo "Cria primeiro um projeto."; exit; }

$prefill_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$t = [
  'project_id' => (int)(($prefill_project>0)?$prefill_project:($projects[0]['id'] ?? 0)),
  'title' => '',
  'description_html' => '',
  'status' => 'Aberta',
  'priority' => 'Normal',
  'start_date_utc' => null,
  'due_date_utc' => null
];

if ($id) {
  $st = db()->prepare("SELECT * FROM project_tasks WHERE id=?");
  $st->execute([$id]);
  $t = $st->fetch();
  if (!$t) { echo "Tarefa não encontrada."; exit; }
}

if (isset($_GET['del']) && $id) {
  csrf_check();
  $st = db()->prepare("DELETE FROM project_tasks WHERE id=?");
  $st->execute([$id]);
  redirect('index.php?p=tasks_list&project_id=' . (int)$t['project_id']);
}

function utcToLocalInput(?string $utc): string {
  if (!$utc) return '';
  $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
  return $dt->setTimezone(new DateTimeZone('Europe/Lisbon'))->format('Y-m-d\TH:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $project_id = (int)($_POST['project_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $description_html = (string)($_POST['description_html'] ?? '');
  $status = trim($_POST['status'] ?? 'Aberta');
  $priority = trim($_POST['priority'] ?? 'Normal');

  $tz = new DateTimeZone('Europe/Lisbon');
  $start_local = trim($_POST['start_local'] ?? '');
  $due_local = trim($_POST['due_local'] ?? '');

  $start_utc = null;
  $due_utc = null;

  if ($start_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start_local, $tz);
    if ($d) $start_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }
  if ($due_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $due_local, $tz);
    if ($d) $due_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }

  $done_utc = null;
  if ($status === 'Concluída') $done_utc = nowUtc()->format('Y-m-d H:i:s');

  $userId = (int)($_SESSION['user']['id'] ?? 0);

  if ($id) {
    $st = db()->prepare("UPDATE project_tasks SET project_id=?, title=?, description_html=?, status=?, priority=?, start_date_utc=?, due_date_utc=?, done_at_utc=? WHERE id=?");
    $st->execute([$project_id, $title, $description_html ?: null, $status, $priority, $start_utc, $due_utc, $done_utc, $id]);
  } else {
    $st = db()->prepare("INSERT INTO project_tasks(project_id,title,description_html,status,priority,start_date_utc,due_date_utc,done_at_utc,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    $st->execute([$project_id, $title, $description_html ?: null, $status, $priority, $start_utc, $due_utc, $done_utc, $userId]);
    $id = (int)db()->lastInsertId();
  }

  redirect('index.php?p=tasks_list&project_id=' . $project_id);
}

$statuses = ['Aberta','Em curso','Bloqueada','Concluída'];
$priorities = ['Baixa','Normal','Alta','Crítica'];
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0"><?= $id ? 'Editar tarefa' : 'Nova tarefa' ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="index.php?p=tasks_list"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div class="pb-card p-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div class="col-12 col-lg-6">
      <label class="form-label">Projeto</label>
      <select class="form-select" name="project_id" required>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$t['project_id']===(int)$p['id'])?'selected':'' ?>><?= h($p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-lg-6">
      <label class="form-label">Título</label>
      <input class="form-control" name="title" required value="<?= h($t['title']) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Início (local)</label>
      <input class="form-control" type="datetime-local" name="start_local" value="<?= h(utcToLocalInput($t['start_date_utc'])) ?>">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Prazo (local)</label>
      <input class="form-control" type="datetime-local" name="due_local" value="<?= h(utcToLocalInput($t['due_date_utc'])) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Estado</label>
      <select class="form-select" name="status">
        <?php foreach ($statuses as $s): ?>
          <option value="<?= h($s) ?>" <?= ($t['status']===$s)?'selected':'' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Prioridade</label>
      <select class="form-select" name="priority">
        <?php foreach ($priorities as $pr): ?>
          <option value="<?= h($pr) ?>" <?= ($t['priority']===$pr)?'selected':'' ?>><?= h($pr) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 mt-2">
      <label class="form-label">Descrição (HTML)</label>
      <textarea id="description_html" name="description_html" class="form-control" rows="10"><?= h((string)$t['description_html']) ?></textarea>
    </div>

    <div class="col-12 mt-3 d-flex gap-2">
      <button class="btn pb-btn-gold"><i class="bi bi-check2"></i> Guardar</button>
      <?php if ($id): ?>
        <a class="btn btn-outline-secondary" href="index.php?p=task_edit&id=<?= $id ?>&del=1&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar esta tarefa?')">
          <i class="bi bi-trash"></i> Apagar
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#description_html',
    height: 420,
    menubar: false,
    plugins: 'lists link table code autoresize',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link table | code'
  });
</script>
