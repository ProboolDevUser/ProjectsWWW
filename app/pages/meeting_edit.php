<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$projects = db()->query("SELECT id, title FROM projects WHERE is_active=1 ORDER BY title ASC")->fetchAll();
if (!$projects) { echo "Cria primeiro um projeto."; exit; }

$prefill_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$m = [
  'project_id' => (int)(($prefill_project>0)?$prefill_project:($projects[0]['id'] ?? 0)),
  'title' => '',
  'starts_at_utc' => null,
  'ends_at_utc' => null,
  'location' => '',
  'link_url' => '',
  'minutes_html' => ''
];

if ($id) {
  $st = db()->prepare("SELECT * FROM project_meetings WHERE id=?");
  $st->execute([$id]);
  $m = $st->fetch();
  if (!$m) { echo "Reunião não encontrada."; exit; }
}

if (isset($_GET['del']) && $id) {
  csrf_check();
  $st = db()->prepare("DELETE FROM project_meetings WHERE id=?");
  $st->execute([$id]);
  redirect('index.php?p=meetings_list&project_id=' . (int)$m['project_id']);
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
  $location = trim($_POST['location'] ?? '');
  $link_url = trim($_POST['link_url'] ?? '');
  $minutes_html = (string)($_POST['minutes_html'] ?? '');

  $tz = new DateTimeZone('Europe/Lisbon');
  $starts_local = trim($_POST['starts_local'] ?? '');
  $ends_local = trim($_POST['ends_local'] ?? '');

  $starts_utc = null;
  $ends_utc = null;

  if ($starts_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $starts_local, $tz);
    if ($d) $starts_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }
  if ($ends_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $ends_local, $tz);
    if ($d) $ends_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }

  $userId = (int)($_SESSION['user']['id'] ?? 0);

  if ($id) {
    $st = db()->prepare("UPDATE project_meetings SET project_id=?, title=?, starts_at_utc=?, ends_at_utc=?, location=?, link_url=?, minutes_html=? WHERE id=?");
    $st->execute([$project_id, $title, $starts_utc, $ends_utc, $location ?: null, $link_url ?: null, $minutes_html ?: null, $id]);
  } else {
    $st = db()->prepare("INSERT INTO project_meetings(project_id,title,starts_at_utc,ends_at_utc,location,link_url,minutes_html,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $st->execute([$project_id, $title, $starts_utc ?? nowUtc()->format('Y-m-d H:i:s'), $ends_utc, $location ?: null, $link_url ?: null, $minutes_html ?: null, $userId]);
    $id = (int)db()->lastInsertId();
  }

  redirect('index.php?p=meetings_list&project_id=' . $project_id);
}
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0"><?= $id ? 'Editar reunião' : 'Nova reunião' ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="index.php?p=meetings_list"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<div class="pb-card p-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div class="col-12 col-lg-6">
      <label class="form-label">Projeto</label>
      <select class="form-select" name="project_id" required>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$m['project_id']===(int)$p['id'])?'selected':'' ?>><?= h($p['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-lg-6">
      <label class="form-label">Título</label>
      <input class="form-control" name="title" required value="<?= h($m['title']) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Início (local)</label>
      <input class="form-control" type="datetime-local" name="starts_local" value="<?= h(utcToLocalInput($m['starts_at_utc'])) ?>" required>
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Fim (local)</label>
      <input class="form-control" type="datetime-local" name="ends_local" value="<?= h(utcToLocalInput($m['ends_at_utc'])) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Local</label>
      <input class="form-control" name="location" value="<?= h((string)$m['location']) ?>" placeholder="Ex.: Online / Sala / Cliente">
    </div>
    <div class="col-12 col-md-6">
      <label class="form-label">Link</label>
      <input class="form-control" name="link_url" value="<?= h((string)$m['link_url']) ?>" placeholder="Teams/Zoom/URL">
    </div>

    <div class="col-12 mt-2">
      <label class="form-label">Acta / notas (HTML)</label>
      <textarea id="minutes_html" name="minutes_html" class="form-control" rows="10"><?= h((string)$m['minutes_html']) ?></textarea>
    </div>

    <div class="col-12 mt-3 d-flex gap-2">
      <button class="btn pb-btn-gold"><i class="bi bi-check2"></i> Guardar</button>
      <?php if ($id): ?>
        <a class="btn btn-outline-secondary" href="index.php?p=meeting_edit&id=<?= $id ?>&del=1&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar esta reunião?')">
          <i class="bi bi-trash"></i> Apagar
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#minutes_html',
    height: 380,
    menubar: false,
    plugins: 'lists link table code autoresize',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link table | code'
  });
</script>
