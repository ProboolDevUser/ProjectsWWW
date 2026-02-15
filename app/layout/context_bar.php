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
<div class="pb-contextbar">
  <form method="post" action="index.php?p=context_set" class="pb-contextform">
    <?= csrf_field() ?>
    <input type="hidden" name="_back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">

    <div class="pb-contextitem">
      <span class="pb-contexticon" aria-hidden="true">🏢</span>
      <select name="client_id" class="pb-contextselect" onchange="this.form.project_id.value=''; this.form.submit();">
        <option value="0">Selecionar cliente</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((int)$c['id'] === $activeClientId) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="pb-contextitem">
      <span class="pb-contexticon" aria-hidden="true">📁</span>
      <select name="project_id" class="pb-contextselect" onchange="this.form.submit();" <?= ($activeClientId <= 0) ? 'disabled' : '' ?>>
        <option value="0"><?= ($activeClientId <= 0) ? 'Seleciona primeiro um cliente' : 'Selecionar projeto' ?></option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === $activeProjectId) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>
