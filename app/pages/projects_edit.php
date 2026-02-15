<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';
require_role(['admin','consultor']);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$clients = db()->query("SELECT id, name FROM clients WHERE is_active=1 ORDER BY name ASC")->fetchAll();

$project = [
  'client_id' => $clients[0]['id'] ?? 0,
  'title' => '',
  'code' => '',
  'start_date_utc' => null,
  'due_date_utc' => null,
  'color_tag' => '#2D6CDF',
  'is_active' => 1
];

if ($id) {
  $st = db()->prepare("SELECT * FROM projects WHERE id=?");
  $st->execute([$id]);
  $project = $st->fetch();
  if (!$project) { echo "Projeto não encontrado."; exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $client_id = (int)($_POST['client_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $code = trim($_POST['code'] ?? '');
  $color_tag = trim($_POST['color_tag'] ?? '#2D6CDF');
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  // datas locais -> UTC (assumindo input type=datetime-local)
  $tz = new DateTimeZone('Europe/Lisbon');
  $start_local = trim($_POST['start_local'] ?? '');
  $due_local = trim($_POST['due_local'] ?? '');

  $start_utc = null;
  if ($start_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start_local, $tz);
    if ($d) $start_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }
  $due_utc = null;
  if ($due_local !== '') {
    $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $due_local, $tz);
    if ($d) $due_utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  }

  if ($id) {
    $st = db()->prepare("UPDATE projects SET client_id=?, title=?, code=?, start_date_utc=?, due_date_utc=?, color_tag=?, is_active=? WHERE id=?");
    $st->execute([$client_id, $title, $code ?: null, $start_utc, $due_utc, $color_tag ?: null, $is_active, $id]);
  } else {
    $st = db()->prepare("INSERT INTO projects(client_id,title,code,start_date_utc,due_date_utc,color_tag,is_active) VALUES (?,?,?,?,?,?,?)");
    $st->execute([$client_id, $title, $code ?: null, $start_utc, $due_utc, $color_tag ?: null, $is_active]);
    $id = (int)db()->lastInsertId();
  }

  redirect('index.php?p=projects_list');
}

function utcToLocalInput(?string $utc): string {
  if (!$utc) return '';
  $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
  return $dt->setTimezone(new DateTimeZone('Europe/Lisbon'))->format('Y-m-d\TH:i');
}

?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h5 mb-0"><?= $id ? 'Editar projeto' : 'Novo projeto' ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="index.php?p=projects_list">Voltar</a>
</div>

<div class="pb-card p-3">
  <form method="post" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

    <div class="col-12 col-md-6">
      <label class="form-label">Cliente</label>
      <select class="form-select" name="client_id" required>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)$project['client_id']===(int)$c['id'])?'selected':'' ?>>
            <?= h($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Código</label>
      <input class="form-control" name="code" value="<?= h((string)$project['code']) ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Título</label>
      <input class="form-control" name="title" required value="<?= h($project['title']) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Início (local)</label>
      <input class="form-control" type="datetime-local" name="start_local" value="<?= h(utcToLocalInput($project['start_date_utc'])) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Prazo (local)</label>
      <input class="form-control" type="datetime-local" name="due_local" value="<?= h(utcToLocalInput($project['due_date_utc'])) ?>">
    </div>

    <div class="col-12 col-md-6">
      <label class="form-label">Cor do cartão</label>
      <input class="form-control form-control-color" type="color" name="color_tag" value="<?= h((string)($project['color_tag'] ?: '#2D6CDF')) ?>">
    </div>

    <div class="col-12 col-md-6 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= ((int)$project['is_active']===1)?'checked':'' ?>>
        <label class="form-check-label" for="is_active">Ativo</label>
      </div>
    </div>

    <div class="col-12 mt-3">
      <button class="btn btn-primary">Guardar</button>
    </div>
  </form>
</div>