<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
require_once __DIR__ . '/../display_master.php';
header('Content-Type: application/json; charset=UTF-8');
$actor = requirePermission($pdo, 'create_account_flg');
echo json_encode([
    'me' => ['role_id' => (int)$actor['role_id'], 'is_system' => isSystemRole($actor)],
    'display_master' => standardDisplayData($pdo),
], JSON_UNESCAPED_UNICODE);
