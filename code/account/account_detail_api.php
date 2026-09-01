<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
require_once __DIR__ . '/../display_master.php';

header('Content-Type: application/json; charset=UTF-8');
$actor = requirePermission($pdo, 'account_flg');
$targetId = (int)($_GET['user_id'] ?? 0);
if ($targetId <= 0) {
    denyRequest(400, 'invalid_user_id');
}

$columns = implode(', ', array_map(static fn(string $column): string => "r.$column", ROLE_PERMISSION_COLUMNS));
$stmt = $pdo->prepare("SELECT u.id, u.login_id, u.name, u.role_id, $columns FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? LIMIT 1");
$stmt->execute([$targetId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
if (!$user) {
    denyRequest(404, 'user_not_found');
}
$targetRole = $user + ['user_id' => $user['id']];
if (!canEditUserPermissions($actor, $targetRole)) {
    denyRequest(403, 'cannot_change_this_user');
}

echo json_encode([
    'me' => ['is_system' => isSystemRole($actor)],
    'user' => $user,
    'display_master' => standardDisplayData($pdo),
], JSON_UNESCAPED_UNICODE);
