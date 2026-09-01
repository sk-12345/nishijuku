<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
header('Content-Type: application/json; charset=UTF-8');
$actor = requirePermission($pdo, 'account_flg');
$myId = (int)$actor['user_id'];
$auditUser = (string)($_SESSION['user']['login_id'] ?? $myId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') !== 'change_password') denyRequest(400, 'unknown_action');
    $targetId = (int)($_POST['user_id'] ?? 0);
    $newPass = (string)($_POST['new_password'] ?? '');
    if ($targetId <= 0 || mb_strlen($newPass) < 4) denyRequest(400, 'invalid_params');
    $target = roleByUserId($pdo, $targetId);
    if (!$target) denyRequest(404, 'user_not_found');
    if (!canManageUser($actor, $target)) denyRequest(403, 'cannot_change_this_user');
    $stmt = $pdo->prepare("UPDATE users SET password_hash=?, update_datetime=NOW(), update_user_id=?, update_func_id='account_password', lock_timestamp=CURRENT_TIMESTAMP WHERE id=?");
    $stmt->execute([password_hash($newPass, PASSWORD_DEFAULT), $auditUser, $targetId]);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $permissionColumns = implode(', ', array_map(
        static fn(string $column): string => "r.$column",
        ROLE_PERMISSION_COLUMNS
    ));
    $users = $pdo->query("SELECT u.id, u.login_id, u.name, u.role_id, $permissionColumns FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.role_id, u.id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$user) {
        $targetRole = $user + ['user_id' => $user['id']];
        $user['can_change'] = canEditUserPermissions($actor, $targetRole);
        $user['can_delete'] = canManageUser($actor, $targetRole);
        $user['can_change_password'] = canManageUser($actor, $targetRole);
    }
    unset($user);
    echo json_encode([
        'me' => [
            'id' => $myId,
            'role_id' => (int)$actor['role_id'],
            'is_system' => isSystemRole($actor),
        ],
        'users' => $users,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    denyRequest(500, 'db_error');
}
