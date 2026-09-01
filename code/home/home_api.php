<?php
session_start();

require_once '../db.php';
require_once '../permissions.php';
require_once '../display_master.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$user = $_SESSION['user'];
$role = currentRole($pdo);

$roleId = isset($user['role_id']) ? (int)$user['role_id'] : 0;

echo json_encode([
    'user' => [
        'fullname' => $user['fullname'] ?? '',
        'role_id' => $roleId
    ],
    'flags' => [
        'create_account' => hasPermission($role, 'create_account_flg'),
        'account' => hasPermission($role, 'account_flg'),
        'update_confirmation' => hasPermission($role, 'update_confirmation_flg'),
        'practice' => hasPermission($role, 'practice_flg'),
        'game' => hasPermission($role, 'game_flg'),
        'event' => hasPermission($role, 'event_flg')
    ],
    'display_master' => standardDisplayData($pdo)
], JSON_UNESCAPED_UNICODE);
