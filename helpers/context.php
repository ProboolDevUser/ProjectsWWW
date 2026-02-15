<?php

function get_active_client_id(): ?int {
    return isset($_SESSION['active_client_id'])
        ? (int)$_SESSION['active_client_id']
        : null;
}

function get_active_project_id(): ?int {
    return isset($_SESSION['active_project_id'])
        ? (int)$_SESSION['active_project_id']
        : null;
}

function set_active_context(?int $clientId, ?int $projectId): void {

    if ($clientId && $clientId > 0) {
        $_SESSION['active_client_id'] = $clientId;
    } else {
        unset($_SESSION['active_client_id']);
    }

    if ($projectId && $projectId > 0) {
        $_SESSION['active_project_id'] = $projectId;
    } else {
        unset($_SESSION['active_project_id']);
    }
}

function clear_project_context(): void {
    unset($_SESSION['active_project_id']);
}

function require_context(): void {

    if (!get_active_client_id() || !get_active_project_id()) {

        echo '<div class="context-warning">
                Selecione um cliente e um projeto.
              </div>';
        exit;
    }
}
