<?php
declare(strict_types=1);

$u = current_user();
$role = current_user_role();
$userId = (int)($u['id'] ?? 0);

$activeClientId  = (int)(get_active_client_id() ?? 0);
$activeProjectId = (int)(get_active_project_id() ?? 0);

$clients = $userId > 0 ? context_clients_for_user($userId, $role) : [];

if ($activeClientId <= 0 && $role === 'trainer' && count($clients) === 1) {
  // opcional: pré-selecionar se só existir 1 cliente
  $activeClientId = (int)$clients[0]['id'];
}

$projects = ($userId > 0 && $activeClientId > 0) ? context_projects_for_user($userId, $role, $activeClientId) : [];
?>
<div class="pb-context-inline">
  <form method="post" action="index.php?p=context_set" class="pb-contextform-inline">
    <?= csrf_field() ?>
    <input type="hidden" name="_back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">

    <div class="pb-contextfield">
      <span class="pb-ic" aria-hidden="true">
        <!-- building icon (inline svg) -->
        <svg viewBox="0 0 24 24" width="18" height="18">
          <path fill="currentColor" d="M4 21V3h10v6h6v12H4zm2-2h2v-2H6v2zm0-4h2v-2H6v2zm0-4h2V9H6v2zm0-4h2V5H6v2zm4 12h2v-2h-2v2zm0-4h2v-2h-2v2zm0-4h2V9h-2v2zm0-4h2V5h-2v2zm6 12h2v-2h-2v2zm0-4h2v-2h-2v2z"/>
        </svg>
      </span>

      <select name="client_id" class="pb-contextselect" onchange="this.form.project_id.value=''; this.form.submit();">
        <option value="0">Cliente</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $activeClientId) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="pb-contextfield">
      <span class="pb-ic" aria-hidden="true">
        <!-- folder icon (inline svg) -->
        <svg viewBox="0 0 24 24" width="18" height="18">
          <path fill="currentColor" d="M10 4l2 2h8c1.1 0 2 .9 2 2v10c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2h6z"/>
        </svg>
      </span>

      <select name="project_id" class="pb-contextselect" onchange="this.form.submit();" <?= ($activeClientId <= 0) ? 'disabled' : '' ?>>
        <option value="0"><?= ($activeClientId <= 0) ? 'Projeto (seleciona cliente)' : 'Projeto' ?></option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $activeProjectId) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

