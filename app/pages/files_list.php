<?php
require_once __DIR__ . '/../helpers.php';
require_login();

$titleMap = [
  'clients_list' => 'Clientes',
  'projects_list' => 'Projetos',
  'users_list' => 'Utilizadores',
  'meetings_list' => 'Reuniões',
  'notes_list' => 'Notas',
  'tasks_list' => 'Tarefas',
  'files_list' => 'Ficheiros',
  'items_all' => 'Tudo',
];

$self = basename(__FILE__, '.php');
$title = $titleMap[$self] ?? 'Página';
?>
<div class="pb-content">
  <div class="pb-card pb-card--flat">
    <h2 class="pb-h2"><?= htmlspecialchars($title) ?></h2>
    <p class="pb-muted">Em desenvolvimento. Esta área vai listar e permitir operar sobre registos do projeto ativo (quando estiver definido).</p>
  </div>
</div>
