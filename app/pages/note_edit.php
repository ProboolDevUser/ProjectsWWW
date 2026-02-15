<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$projects = db()->query("SELECT id, title FROM projects WHERE is_active=1 ORDER BY title ASC")->fetchAll();
if (!$projects) { echo "Cria primeiro um projeto."; exit; }

$prefill_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$n = [
  'project_id' => (int)(($prefill_project>0)?$prefill_project:($projects[0]['id'] ?? 0)),
  'title' => '',
  'note_date_utc' => null,
  'note_html' => ''
];

if ($id) {
  $st = db()->prepare("SELECT * FROM project_notes WHERE id=?");
  $st->execute([$id]);
  $n = $st->fetch();
  if (!$n) { echo "Nota não encontrada."; exit; }
}

if (isset($_GET['del']) && $id) {
  csrf_check();
  $st = db()->prepare("DELETE FROM project_notes WHERE id=?");
  $st->execute([$id]);
  redirect('index.php?p=notes_list&project_id=' . (int)$n['project_id']);
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
  $note_html = (string)($_POST['note_html'] ?? '');

  $tz = new DateTimeZone('Europe/Lisbon');
  $note_local = trim($_POST['note_local'] ?? '');
  $note_utc = null;
  if ($note_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $note_local, $tz);
    if ($d) $note_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  } else {
    $note_utc = nowUtc()->format('Y-m-d H:i:s');
  }

  $userId = (int)($_SESSION['user']['id'] ?? 0);

  if ($id) {
    $st = db()->prepare("UPDATE project_notes SET project_id=?, title=?, note_html=?, note_date_utc=? WHERE id=?");
    $st->execute([$project_id, $title, $note_html, $note_utc, $id]);
  } else {
    $st = db()->prepare("INSERT INTO project_notes(project_id,title,note_html,note_date_utc,created_by) VALUES (?,?,?,?,?)");
    $st->execute([$project_id, $title, $note_html, $note_utc, $userId]);
    $id = (int)db()->lastInsertId();
  }

  redirect('index.php?p=notes_list&project_id=' . $project_id);
}
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0"><?= $id ? 'Editar nota' : 'Nova nota' ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="index.php?p=notes_list"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div class="pb-card p-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div class="col-12 col-lg-6">
      <label class="form-label">Projeto</label>
      <select class="form-select" name="project_id" required>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$n['project_id']===(int)$p['id'])?'selected':'' ?>><?= h($p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-lg-6">
      <label class="form-label">Data (local)</label>
      <input class="form-control" type="datetime-local" name="note_local" value="<?= h(utcToLocalInput($n['note_date_utc'])) ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Título</label>
      <input class="form-control" name="title" required value="<?= h($n['title']) ?>">
    </div>

    <div class="col-12 mt-2">
      <label class="form-label">Conteúdo (HTML)</label>
      <textarea id="note_html" name="note_html" class="form-control" rows="10"><?= h((string)$n['note_html']) ?></textarea>
    </div>

    <div class="col-12 mt-3 d-flex gap-2">
      <button class="btn pb-btn-gold"><i class="bi bi-check2"></i> Guardar</button>
      <?php if ($id): ?>
        <a class="btn btn-outline-secondary" href="index.php?p=note_edit&id=<?= $id ?>&del=1&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar esta nota?')">
          <i class="bi bi-trash"></i> Apagar
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#note_html',
    height: 420,
    menubar: false,
    plugins: 'lists link table code autoresize',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link table | code'
  });
</script>
