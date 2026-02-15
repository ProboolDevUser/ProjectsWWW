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

function pdo_or_fail(): PDO {
    $pdo = $GLOBALS['pdo'] ?? null;
    if (!$pdo && function_exists('db')) $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('PDO não disponível.');
    }
    return $pdo;
}

function context_clients_for_user(int $userId, string $role): array {
    $pdo = pdo_or_fail();

    if ($role === 'admin') {
        $st = $pdo->query("SELECT id, name FROM clients WHERE is_active = 1 ORDER BY name");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'consultant') {
        $st = $pdo->prepare("
            SELECT c.id, c.name
            FROM clients c
            INNER JOIN user_clients uc ON uc.client_id = c.id
            WHERE uc.user_id = ? AND c.is_active = 1
            ORDER BY c.name
        ");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // trainer: clientes derivados dos projetos atribuídos
    $st = $pdo->prepare("
        SELECT DISTINCT c.id, c.name
        FROM clients c
        INNER JOIN projects p ON p.client_id = c.id
        INNER JOIN user_projects up ON up.project_id = p.id
        WHERE up.user_id = ? AND c.is_active = 1 AND p.is_active = 1
        ORDER BY c.name
    ");
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function context_projects_for_user(int $userId, string $role, int $clientId): array {
    $pdo = pdo_or_fail();

    if ($clientId <= 0) return [];

    if ($role === 'admin') {
        $st = $pdo->prepare("
            SELECT id, title
            FROM projects
            WHERE client_id = ? AND is_active = 1
            ORDER BY title
        ");
        $st->execute([$clientId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'consultant') {
        // só projetos de clientes atribuídos
        $st = $pdo->prepare("
            SELECT p.id, p.title
            FROM projects p
            INNER JOIN user_clients uc ON uc.client_id = p.client_id
            WHERE uc.user_id = ? AND p.client_id = ? AND p.is_active = 1
            ORDER BY p.title
        ");
        $st->execute([$userId, $clientId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // trainer: só projetos atribuídos
    $st = $pdo->prepare("
        SELECT p.id, p.title
        FROM projects p
        INNER JOIN user_projects up ON up.project_id = p.id
        WHERE up.user_id = ? AND p.client_id = ? AND p.is_active = 1
        ORDER BY p.title
    ");
    $st->execute([$userId, $clientId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

