<?php
declare(strict_types=1);

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$clientId  = isset($_POST['client_id']) ? (int)$_POST['client_id'] : null;
$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;

set_active_context($clientId, $projectId);

redirect('index.php');
