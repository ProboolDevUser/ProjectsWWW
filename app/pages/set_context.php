<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Aceita GET ou POST
$client_id  = (int)($_REQUEST['client_id'] ?? 0);
$project_id = (int)($_REQUEST['project_id'] ?? 0);

// Validar cliente
if ($client_id > 0) {
  $c = db_one("SELECT id, name FROM clients WHERE id = ?", [$client_id]);
  if ($c) {
    $_SESSION['ctx_client_id'] = (int)$c['id'];
    $_SESSION['ctx_client_name'] = $c['name'];
  }
} else {
  unset($_SESSION['ctx_client_id'], $_SESSION['ctx_client_name']);
  // se limpar cliente, limpa também projeto
  unset($_SESSION['ctx_project_id'], $_SESSION['ctx_project_title']);
}

// Validar projeto (só se houver cliente selecionado ou se projeto for global)
if ($project_id > 0) {
  // se houver cliente ativo, obriga o projeto a pertencer ao cliente
  if (!empty($_SESSION['ctx_client_id'])) {
    $p = db_one("SELECT id, title FROM projects WHERE id=? AND client_id=?", [$project_id, (int)$_SESSION['ctx_client_id']]);
  } else {
    $p = db_one("SELECT id, title FROM projects WHERE id=?", [$project_id]);
  }
  if ($p) {
    $_SESSION['ctx_project_id'] = (int)$p['id'];
    $_SESSION['ctx_project_title'] = $p['title'];
  }
} else {
  unset($_SESSION['ctx_project_id'], $_SESSION['ctx_project_title']);
}

$back = $_REQUEST['back'] ?? 'dashboard';
// segurança mínima: permitir apenas p=...
if (!preg_match('/^[a-z0-9_]+$/i', $back)) $back = 'dashboard';

header('Location: index.php?p=' . $back);
exit;
