<?php
$p    = $_GET['p'] ?? 'dashboard';
$cur  = $p;
$user = function_exists('current_user') ? current_user() : null;

/* Páginas que pertencem ao módulo "Tabelas base" */
$isBaseTablesModule = in_array($p, [
  'base_tables','clients_list','projects_list','users_list',
  'clients_edit','projects_edit','users_edit'
], true);
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ProBool Project Hub</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="assets/app.css" rel="stylesheet">
</head>

<body class="<?php echo ($isBaseTablesModule ? 'pb-base-tables' : ''); ?>">
<div class="pb-appframe">

<nav class="navbar pb-topbar">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#pbOffcanvas" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>

	  <a class="navbar-brand pb-brand-wrap d-flex align-items-center justify-content-center" href="index.php?p=dashboard" aria-label="ProBool Project Hub">
        <img class="pb-logo" src="assets/img/ProboolProjectHub.png" alt="ProBool Project Hub">
      </a>
    </div>

    <?php if ($user): ?>

      <?php /* Tabs removidas: duplicavam a navegação existente na barra lateral. */ ?>

      <div class="d-flex align-items-center gap-2">
        <div class="pb-user d-none d-md-flex">
          <img src="index.php?p=user_photo" alt="Utilizador">
          <div>
            <div class="name"><?= h($user['name'] ?? $user['full_name'] ?? $user['username'] ?? $user['login'] ?? '') ?></div>
            <div class="role">Utilizador</div>
          </div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="index.php?p=logout" title="Sair"><i class="bi bi-box-arrow-right"></i></a>
      </div>

    <?php endif; ?>

  </div>
</nav>

<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="pbOffcanvas">
  <div class="offcanvas-header">
    <img class="pb-logo" src="assets/img/ProboolProjectHub.png" alt="ProBool Project Hub">
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
<?php include __DIR__ . '/sidebar.php'; ?>
  </div>
</div>

<div class="container-fluid py-3 pb-shell">
  <div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-auto pb-col-sidebar">
      <div class="pb-sidebar p-2 d-none d-lg-block">
        <?php include __DIR__ . '/sidebar.php'; ?>
      </div>
    </div>

    <main class="col-12 col-lg">
