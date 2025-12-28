<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$user = $_SESSION['user'];

$roleId = isset($user['role_id']) ? (int)$user['role_id'] : 0;

$roleNameMap = [
    1 => 'SYSTEM',
    2 => 'ADMIN',
    3 => 'PHOTO',
    4 => 'GENERAL',
];

$roleName = $roleNameMap[$roleId] ?? ('UNKNOWN(' . $roleId . ')');
$isAdminOrSystem = in_array($roleId, [1, 2], true);

echo json_encode([
    'user' => [
        'fullname' => $user['fullname'] ?? '',
        'role_id' => $roleId,
        'role_name' => $roleName
    ],
    'flags' => [
        'is_admin_or_system' => $isAdminOrSystem
    ]
], JSON_UNESCAPED_UNICODE);
