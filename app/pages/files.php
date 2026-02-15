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

$cfg = require __DIR__ . '/../config.php';
$uploadsDir = $cfg['app']['uploads_dir'];
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0775, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  $project_id_post = (int)($_POST['project_id'] ?? 0);
  if ($project_id_post <= 0) { echo "Escolhe um projeto."; exit; }

  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo "Upload falhou."; exit;
  }

  $tmp = $_FILES['file']['tmp_name'];
  $orig = $_FILES['file']['name'];
  $size = (int)$_FILES['file']['size'];
  $mime = $_FILES['file']['type'] ?? null;

  $safeOrig = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
  $stored = gmdate('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeOrig;

  $projDirRel = (string)$project_id_post;
  $projDirAbs = rtrim($uploadsDir, '/\\') . DIRECTORY_SEPARATOR . $projDirRel;
  if (!is_dir($projDirAbs)) mkdir($projDirAbs, 0775, true);

  $destAbs = $projDirAbs . DIRECTORY_SEPARATOR . $stored;
  if (!move_uploaded_file($tmp, $destAbs)) {
    echo "Não foi possível guardar o ficheiro."; exit;
  }

  $relPath = 'uploads/' . $projDirRel . '/' . $stored;
  $userId = (int)($_SESSION['user']['id'] ?? 0);

  $st = db()->prepare("INSERT INTO project_files(project_id,original_name,stored_name,relative_path,size_bytes,mime_type,uploaded_by) VALUES (?,?,?,?,?,?,?)");
  $st->execute([$project_id_post, $orig, $stored, $relPath, $size, $mime, $userId]);

  redirect('index.php?p=files&project_id=' . $project_id_post);
}

if (isset($_GET['del']) && isset($_GET['id'])) {
  csrf_check();
  $id = (int)$_GET['id'];
  $st = db()->prepare("SELECT * FROM project_files WHERE id=?");
  $st->execute([$id]);
  $f = $st->fetch();
  if ($f) {
    $abs = __DIR__ . '/../../public/' . $f['relative_path'];
    if (is_file($abs)) @unlink($abs);
    $st = db()->prepare("DELETE FROM project_files WHERE id=?");
    $st->execute([$id]);
    redirect('index.php?p=files&project_id=' . (int)$f['project_id']);
  }
}

$where = "1=1";
$params = [];
if ($project_id > 0) { $where .= " AND f.project_id=?"; $params[] = $project_id; }

$sql = "SELECT f.*, p.title project_title
        FROM project_files f
        JOIN projects p ON p.id=f.project_id
        WHERE $where
        ORDER BY f.uploaded_at_utc DESC
        LIMIT 500";
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<div class="pb-page-title mb-3">
  <h1 class="h5 m-0">Ficheiros</h1>
</div>

<div class="pb-card p-3 mb-3">
  <?= project_select($projects, $project_id, 'files') ?>

  <div class="pb-divider"></div>

  <form method="post" enctype="multipart/form-data" class="row g-2 align-items-end">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div class="col-12 col-md-5">
      <label class="form-label">Projeto (para upload)</label>
      <select class="form-select" name="project_id" required>
        <option value="0">Escolhe...</option>
        <?php foreach ($projects as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>" <?= ($project_id===(int)$pr['id'])?'selected':'' ?>><?= h($pr['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-5">
      <label class="form-label">Ficheiro</label>
      <input class="form-control" type="file" name="file" required>
    </div>
    <div class="col-12 col-md-2">
      <button class="btn pb-btn-gold w-100"><i class="bi bi-upload"></i> Enviar</button>
    </div>
  </form>
</div>

<div class="pb-card p-2">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th class="d-none d-lg-table-cell">Projeto</th>
          <th>Tamanho</th>
          <th>Data</th>
          <th style="width:170px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?><tr><td colspan="5" class="pb-muted">Sem ficheiros.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['original_name']) ?></td>
            <td class="d-none d-lg-table-cell pb-muted"><?= h($r['project_title']) ?></td>
            <td><?= number_format(((int)$r['size_bytes'])/1024, 1) ?> KB</td>
            <td><?= toLisbon($r['uploaded_at_utc']) ?></td>
            <td class="text-end">
              <a class="btn btn-outline-secondary btn-sm" href="<?= h($r['relative_path']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
              <a class="btn btn-outline-secondary btn-sm" href="index.php?p=files&del=1&id=<?= (int)$r['id'] ?>&_csrf=<?= h(csrf_token()) ?>" onclick="return confirm('Apagar este ficheiro?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
