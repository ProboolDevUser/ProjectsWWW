<?php
$cur  = $_GET['p'] ?? 'dashboard';
$u    = function_exists('current_user') ? current_user() : null;
$role = role_norm($u['role'] ?? null);

if (!function_exists('pb_side_link')) {
  function pb_side_link(string $p, string $label, string $icon, string $cur, string $rightHtml = ''): string {
    $active = ($cur === $p) ? 'active' : '';
    return '<a class="pb-side-link '.$active.'" href="index.php?p='.$p.'">'.
             '<span class="pb-icon"><i class="bi '.$icon.'"></i></span>'.
             '<span class="flex-grow-1">'.$label.'</span>'.
             $rightHtml.
           '</a>';
  }
}

if (!function_exists('pb_can_show_clients')) {
  function pb_can_show_clients(string $role): bool {
    return in_array($role, ['admin','consultant'], true);
  }
}
if (!function_exists('pb_can_show_base_tables')) {
  function pb_can_show_base_tables(string $role): bool {
    return in_array($role, ['admin','consultant'], true);
  }
}

$baseModulePages = ['base_tables','clients_list','projects_list','users_list','clients_edit','projects_edit','users_edit'];
$isBaseTablesModule = in_array($cur, $baseModulePages, true);
?>

<?php if ($isBaseTablesModule): ?>
  <div class="p-2 d-flex flex-column h-100">
    <div class="pb-side-title mb-2">Tabelas base</div>

    <?= pb_side_link('clients_list', 'Clientes', 'bi-building', $cur, '<span class="pb-side-mini"><i class="bi bi-pencil"></i></span>') ?>
    <?= pb_side_link('projects_list', 'Projetos', 'bi-grid-1x2', $cur, '<span class="pb-side-mini"><i class="bi bi-pencil"></i></span>') ?>
    <?= pb_side_link('users_list', 'Utilizadores', 'bi-person-badge', $cur, '<span class="pb-side-mini"><i class="bi bi-pencil"></i></span>') ?>

    <div class="mt-auto">
      <div class="pb-divider"></div>
      <?= pb_side_link('dashboard', 'Voltar', 'bi-arrow-left', $cur) ?>
    </div>
  </div>

<?php else: ?>
  <div class="p-2 d-flex flex-column h-100">
    <div>
      <?= pb_side_link('dashboard', 'Dashboard', 'bi-speedometer2', $cur) ?>

      <div class="pb-divider"></div>

      <?= pb_side_link('meetings_list', 'Reuniões', 'bi-calendar-event', $cur) ?>
      <?= pb_side_link('notes_list', 'Notas', 'bi-journal-text', $cur) ?>
      <?= pb_side_link('tasks_list', 'Tarefas', 'bi-check2-square', $cur) ?>
      <?= pb_side_link('files', 'Ficheiros', 'bi-folder2-open', $cur) ?>
    </div>

    <div class="mt-auto">
      <?php if (pb_can_show_base_tables($role)): ?>
        <div class="pb-divider"></div>
        <?= pb_side_link('base_tables', 'Tabelas base', 'bi-database', $cur) ?>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
