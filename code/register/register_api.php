<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$myRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

if (!in_array($myRoleId, [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

// SYSTEM: ADMIN/PHOTO/GENERAL
if ($myRoleId === 1) {
    $selectable = [
        ['id' => 2, 'name' => 'ADMIN'],
        ['id' => 3, 'name' => 'PHOTO'],
        ['id' => 4, 'name' => 'GENERAL'],
    ];
}
// ADMIN: PHOTO/GENERAL
else {
    $selectable = [
        ['id' => 3, 'name' => 'PHOTO'],
        ['id' => 4, 'name' => 'GENERAL'],
    ];
}

echo json_encode([
    'me' => ['role_id' => $myRoleId],
    'selectable_roles' => $selectable
], JSON_UNESCAPED_UNICODE);
