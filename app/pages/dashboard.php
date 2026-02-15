<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Estatísticas (informação apenas)
$clients_active = (int)db_one("SELECT COUNT(*) c FROM clients WHERE is_active=1")['c'];
$projects_active = (int)db_one("SELECT COUNT(*) c FROM projects WHERE is_active=1")['c'];
// Nota: a BD está em inglês (nomes de tabelas/colunas). A UI está em PT.
// As tarefas pertencem a projetos (tabela project_tasks).
$tasks_open = (int)db_one(
    "SELECT COUNT(*) c
       FROM project_tasks
      WHERE status NOT IN ('Concluída','Concluida','Fechada')"
)['c'];

?>
<div class="pb-page-title mb-3 d-flex align-items-center justify-content-between">
  <h1 class="h5 m-0">Dashboard</h1>
</div>

<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="pb-card h-100">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="pb-muted small">Clientes ativos</div>
          <div class="display-6 m-0"><?= (int)$clients_active ?></div>
        </div>
        <div class="pb-stat-emoji" title="Clientes ativos">👥</div>
      </div>
      <div class="pb-muted mt-2 small">Resumo global (informação). Gestão via <b>Tabelas base</b>.</div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="pb-card h-100">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="pb-muted small">Projetos ativos</div>
          <div class="display-6 m-0"><?= (int)$projects_active ?></div>
        </div>
        <div class="pb-stat-emoji" title="Projetos ativos">📁</div>
      </div>
      <div class="pb-muted mt-2 small">Resumo global (informação). Gestão via <b>Tabelas base</b>.</div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="pb-card h-100">
      <div class="d-flex align-items-start justify-content-between">
        <div>
          <div class="pb-muted small">Tarefas por concluir</div>
          <div class="display-6 m-0"><?= (int)$tasks_open ?></div>
        </div>
        <div class="pb-stat-emoji" title="Tarefas por concluir">✅</div>
      </div>
      <div class="pb-muted mt-2 small">Resumo global (informação). Gestão nas páginas de Tarefas.</div>
    </div>
  </div>
</div>
