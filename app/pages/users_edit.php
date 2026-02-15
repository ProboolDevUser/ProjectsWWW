<?php
require_role(['admin','consultant']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new = $id <= 0;

$cols = db_cols('users');
$has = function (string $c) use ($cols): bool { return in_array($c, $cols, true); };

// Mapear nomes de colunas existentes
$col_name  = $has('name')  ? 'name'  : ($has('full_name') ? 'full_name' : 'name');
$col_login = $has('login') ? 'login' : ($has('username')  ? 'username'  : 'username');

$user = [
  'id'        => $id,
  'name'      => '',
  'email'     => '',
  'phone'     => '',
  'title'     => '',
  'is_active' => 1,
  'login'     => '',
  'website'   => '',
  'photo_path' => '',
  'role'      => 'trainer',
];

if (!$is_new) {
  $row = db_one("SELECT * FROM users WHERE id=:id", ['id' => $id]);
  if (!$row) {
    flash('Utilizador não encontrado.', 'error');
    redirect('index.php?p=users_list');
  }
  $user['name']      = $row[$col_name] ?? '';
  $user['email']     = $row['email'] ?? '';
  $user['phone']     = $row['phone'] ?? '';
  $user['title']     = $row['title'] ?? '';
  $user['is_active'] = (int)($row['is_active'] ?? 1);
  $user['login']     = $row[$col_login] ?? '';
  $user['website']   = $row['website'] ?? '';
  $user['photo_path'] = $row['photo_path'] ?? '';
  $user['role']      = role_norm($row['role'] ?? ($row['role_key'] ?? 'trainer'));
}

// Dados para permissões
$clients  = db_all("SELECT id, name FROM clients ORDER BY name");
$projects = db_all("SELECT p.id, p.title, p.client_id FROM projects p ORDER BY p.title");

// Utilizador autenticado (para restrição consultor)
$me      = current_user();
$me_role = role_norm($me['role'] ?? null);
$me_id   = (int)($me['id'] ?? 0);

$me_client_ids = [];
if ($me_role === 'consultant') {
  $me_client_ids = array_map('intval', array_column(db_all("SELECT client_id FROM user_clients WHERE user_id=:u", ['u'=>$me_id]), 'client_id'));
}

// Permissões do utilizador editado
$user_client_ids  = $is_new ? [] : array_map('intval', array_column(db_all("SELECT client_id FROM user_clients WHERE user_id=:u", ['u'=>$id]), 'client_id'));
$user_project_ids = $is_new ? [] : array_map('intval', array_column(db_all("SELECT project_id FROM user_projects WHERE user_id=:u", ['u'=>$id]), 'project_id'));

/**
 * Fotografia do utilizador (PNG circular):
 * - crop central quadrado
 * - resize para 256x256
 * - máscara circular (transparência)
 * Guarda em /public/uploads/users/u<ID>.png e devolve path relativo.
 */
function pb_save_user_photo(int $userId, array $file): ?string {
  if (empty($file['tmp_name'])) return null;
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

  $info = @getimagesize($file['tmp_name']);
  if (!$info) return null;

  $mime = $info['mime'] ?? '';
  $src = null;
  if ($mime === 'image/jpeg') $src = @imagecreatefromjpeg($file['tmp_name']);
  elseif ($mime === 'image/png') $src = @imagecreatefrompng($file['tmp_name']);
  elseif ($mime === 'image/gif') $src = @imagecreatefromgif($file['tmp_name']);
  if (!$src) return null;

  $w = imagesx($src);
  $h = imagesy($src);
  $side = min($w, $h);
  $sx = (int)(($w - $side) / 2);
  $sy = (int)(($h - $side) / 2);

  $dstSize = 256;
  $dst = imagecreatetruecolor($dstSize, $dstSize);
  imagealphablending($dst, false);
  imagesavealpha($dst, true);
  $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
  imagefilledrectangle($dst, 0, 0, $dstSize, $dstSize, $transparent);
  imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dstSize, $dstSize, $side, $side);

  // Máscara circular
  $r = $dstSize / 2;
  for ($y = 0; $y < $dstSize; $y++) {
    for ($x = 0; $x < $dstSize; $x++) {
      $dx = $x - $r + 0.5;
      $dy = $y - $r + 0.5;
      if (($dx * $dx + $dy * $dy) > ($r * $r)) {
        imagesetpixel($dst, $x, $y, $transparent);
      }
    }
  }

  $relDir = 'uploads/users';
  $absDir = __DIR__ . '/../../public/' . $relDir;
  if (!is_dir($absDir)) @mkdir($absDir, 0775, true);

  $rel = $relDir . '/u' . $userId . '.png';
  $abs = __DIR__ . '/../../public/' . $rel;
  @imagepng($dst, $abs, 7);

  imagedestroy($src);
  imagedestroy($dst);

  return $rel;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user['name']      = trim($_POST['name'] ?? '');
  $user['email']     = trim($_POST['email'] ?? '');
  $user['phone']     = trim($_POST['phone'] ?? '');
  $user['title']     = trim($_POST['title'] ?? '');
  $user['is_active'] = isset($_POST['is_active']) ? 1 : 0;
  $user['login']     = trim($_POST['login'] ?? '');
  $user['website']   = trim($_POST['website'] ?? '');
  $user['photo_path'] = trim($_POST['photo_path'] ?? ''); // URL externa (opcional)
  $user['role']      = role_norm($_POST['role'] ?? 'trainer');
  $new_password      = (string)($_POST['new_password'] ?? '');

  if ($user['name'] === '' || $user['login'] === '') {
    flash('Nome e Login são obrigatórios.', 'error');
  } else {
    // Build dynamic SET list
    $fields = [];
    $params = ['id' => $id];

    $fields[$col_name]  = $user['name'];
    $fields[$col_login] = $user['login'];

    if ($has('email'))     $fields['email']     = $user['email'];
    if ($has('phone'))     $fields['phone']     = $user['phone'];
    if ($has('title'))     $fields['title']     = $user['title'];
    if ($has('website'))   $fields['website']   = $user['website'];
    if ($has('photo_path')) $fields['photo_path'] = $user['photo_path'];
    if ($has('role'))      $fields['role']      = $user['role'];
    if ($has('is_active')) $fields['is_active'] = $user['is_active'];

    if ($new_password !== '' && $has('password_hash')) {
      $fields['password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    if ($is_new) {
      // Inserir
      $cols_sql = [];
      $vals_sql = [];
      foreach ($fields as $k=>$v) {
        $cols_sql[] = $k;
        $ph = ':' . $k;
        $vals_sql[] = $ph;
        $params[$k] = $v;
      }
      if ($has('created_at_utc')) {
        $cols_sql[] = 'created_at_utc';
        $vals_sql[] = 'UTC_TIMESTAMP()';
      }
      $sql = "INSERT INTO users (" . implode(',', $cols_sql) . ") VALUES (" . implode(',', $vals_sql) . ")";
      db_exec($sql, $params);
      $id = (int)db()->lastInsertId();
      $is_new = false;
      $user['id'] = $id;
    } else {
      // Atualizar
      $set_sql = [];
      foreach ($fields as $k=>$v) {
        $set_sql[] = "$k = :$k";
        $params[$k] = $v;
      }
      $sql = "UPDATE users SET " . implode(',', $set_sql) . " WHERE id=:id";
      db_exec($sql, $params);
    }

    // Atualizar permissões
    if ($user['role'] === 'consultant') {
      $ids = array_map('intval', $_POST['clients'] ?? []);
      if ($me_role === 'consultant') {
        // consultor só pode atribuir dentro dos seus clientes
        $ids = array_values(array_intersect($ids, $me_client_ids));
      }
      db_exec("DELETE FROM user_clients WHERE user_id=:u", ['u'=>$id]);
      foreach ($ids as $cid) {
        db_exec("INSERT INTO user_clients (user_id, client_id) VALUES (:u,:c)", ['u'=>$id,'c'=>$cid]);
      }
    } else {
      db_exec("DELETE FROM user_clients WHERE user_id=:u", ['u'=>$id]);
    }

    if ($user['role'] === 'trainer') {
      $ids = array_map('intval', $_POST['projects'] ?? []);
      if ($me_role === 'consultant') {
        // consultor só pode atribuir projetos dentro dos seus clientes
        $allowed = [];
        foreach ($projects as $p) {
          if (in_array((int)$p['client_id'], $me_client_ids, true)) $allowed[] = (int)$p['id'];
        }
        $ids = array_values(array_intersect($ids, $allowed));
      }
      db_exec("DELETE FROM user_projects WHERE user_id=:u", ['u'=>$id]);
      foreach ($ids as $pid) {
        db_exec("INSERT INTO user_projects (user_id, project_id) VALUES (:u,:p)", ['u'=>$id,'p'=>$pid]);
      }
    } else {
      db_exec("DELETE FROM user_projects WHERE user_id=:u", ['u'=>$id]);
    }

    // Fotografia (upload) tem prioridade sobre URL externa.
    if (!empty($_FILES['photo_file']) && ($_FILES['photo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $saved = pb_save_user_photo((int)$id, $_FILES['photo_file']);
      if ($saved) {
        db_exec("UPDATE users SET photo_path=:p WHERE id=:id", ['p'=>$saved,'id'=>$id]);
        $user['photo_path'] = $saved;
      }
    }

    flash('Utilizador gravado com sucesso.', 'success');
    redirect('index.php?p=users_list');
  }
}
?>

<div class="pb-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="pb-page-title mb-1"><?= $is_new ? 'Novo utilizador' : 'Editar utilizador' ?></h1>
      <div class="text-muted">Gestão de utilizadores e permissões.</div>
    </div>
    <a class="btn pb-btn-outline" href="index.php?p=users_list">Voltar</a>
  </div>

  <div class="pb-card p-3">
    <form method="post" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="row g-2">
            <div class="col-12">
              <label class="pb-label">Nome</label>
              <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Email</label>
              <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>">
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Telefone</label>
              <input class="form-control" name="phone" value="<?= e($user['phone']) ?>">
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Título</label>
              <input class="form-control" name="title" value="<?= e($user['title']) ?>">
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Página web</label>
              <input class="form-control" name="website" value="<?= e($user['website']) ?>" placeholder="https://">
            </div>
            <div class="col-lg-4">
              <label class="pb-label">Login</label>
              <input class="form-control" name="login" value="<?= e($user['login']) ?>" required>
            </div>
            <div class="col-lg-4">
              <label class="pb-label">Permissões</label>
              <select class="form-select" name="role">
                <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Administrador</option>
                <option value="consultant" <?= $user['role']==='consultant'?'selected':'' ?>>Consultor</option>
                <option value="trainer" <?= $user['role']==='trainer'?'selected':'' ?>>Formador</option>
              </select>
            </div>
            <div class="col-lg-4 d-flex align-items-end">
              <label class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" <?= $user['is_active']? 'checked':'' ?> />
                <span class="form-check-label">Ativo</span>
              </label>
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Password (redefinir)</label>
              <input class="form-control" name="new_password" type="password" placeholder="Deixa vazio para manter">
            </div>
            <div class="col-lg-6">
              <label class="pb-label">Fotografia (URL ou ficheiro)</label>
              <input class="form-control" name="photo_path" value="<?= e($user['photo_path']) ?>" placeholder="https://...">
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <label class="pb-label">Fotografia (carregar)</label>
          <div class="d-flex align-items-center gap-3">
            <?php
              $photo = trim((string)($user['photo_path'] ?? ''));
              $photoSrc = $photo !== '' ? $photo : 'assets/img/avatar.svg';
            ?>
            <img class="pb-avatar pb-avatar--lg" src="<?= e($photoSrc) ?>" alt="Fotografia">
            <div class="flex-grow-1">
              <input class="form-control" type="file" name="photo_file" accept="image/*">
              <div class="text-muted" style="margin-top:6px;">A imagem é gravada com recorte circular.</div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($user['role'] === 'consultant'): ?>
        <hr class="my-4">
        <label class="pb-label">Clientes visíveis (Consultor)</label>
        <select class="form-select" name="clients[]" multiple size="10">
          <?php foreach ($clients as $c):
            if ($me_role === 'consultant' && !in_array((int)$c['id'], $me_client_ids, true)) continue;
            $sel = in_array((int)$c['id'], $user_client_ids, true) ? 'selected' : '';
          ?>
            <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="text-muted" style="margin-top:6px;">Administrador vê tudo. Consultor vê apenas os clientes selecionados.</div>
      <?php elseif ($user['role'] === 'trainer'): ?>
        <hr class="my-4">
        <label class="pb-label">Projetos visíveis (Formador)</label>
        <select class="form-select" name="projects[]" multiple size="10">
          <?php foreach ($projects as $p):
            if ($me_role === 'consultant' && !in_array((int)$p['client_id'], $me_client_ids, true)) continue;
            $sel = in_array((int)$p['id'], $user_project_ids, true) ? 'selected' : '';
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= e($p['title']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="text-muted" style="margin-top:6px;">Um consultor só consegue atribuir projetos dentro dos seus clientes.</div>
      <?php endif; ?>

      <div class="d-flex gap-2 mt-4">
        <button class="btn pb-btn-gold" type="submit">Guardar</button>
        <a class="btn pb-btn-outline" href="index.php?p=users_list">Cancelar</a>
      </div>
    </form>
  </div>
</div>
