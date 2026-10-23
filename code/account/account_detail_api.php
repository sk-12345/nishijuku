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

$columns = implode(', ', array_map(
    static fn(string $column): string => "r.$column",
    ROLE_PERMISSION_COLUMNS
));

$stmt = $pdo->prepare("
    SELECT u.id, u.login_id, u.name, r.id AS role_id, $columns
    FROM users u
    JOIN roles r ON r.id = u.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$targetId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!$user) {
    denyRequest(404, 'user_not_found');
}

$targetRole = $user + ['user_id' => $user['id']];

if (!canEditUserPermissions($actor, $targetRole)) {
    denyRequest(403, 'cannot_change_this_user');
}

$flgs = $pdo->prepare("
    SELECT
        COLUMN_NAME AS physical_name,
        COLUMN_COMMENT AS logical_name
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roles'
      AND COLUMN_NAME LIKE '%flg%'
      AND COLUMN_NAME <> 'system_flg'
    ORDER BY ORDINAL_POSITION
");
$flgs->execute();

$permissionLabels = [];

foreach ($flgs->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permissionLabels[$row['physical_name']] =
        $row['logical_name'] !== ''
            ? $row['logical_name']
            : $row['physical_name'];
}

echo json_encode([
    'me' => ['is_system' => isSystemRole($actor)],
    'user' => $user,
    'display_master' => standardDisplayData($pdo),
    'permission_labels' => (object) $permissionLabels,
], JSON_UNESCAPED_UNICODE);