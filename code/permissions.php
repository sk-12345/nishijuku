<?php
declare(strict_types=1);

const ROLE_PERMISSION_COLUMNS = [
    'system_flg',
    'create_account_flg',
    'account_flg',
    'update_confirmation_flg',
    'practice_flg',
    'game_flg',
    'event_flg',
];

function currentRole(PDO $pdo): array
{
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $columns = implode(', ', array_map(static fn(string $column): string => "r.$column", ROLE_PERMISSION_COLUMNS));
    $stmt = $pdo->prepare("SELECT u.id AS user_id, u.role_id, $columns FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function isSystemRole(array $role): bool
{
    return (int)($role['system_flg'] ?? 0) === 1;
}

function hasPermission(array $role, string $permission): bool
{
    if (!in_array($permission, ROLE_PERMISSION_COLUMNS, true)) {
        return false;
    }
    return isSystemRole($role) || (int)($role[$permission] ?? 0) === 1;
}

function denyRequest(int $status, string $error, bool $json = true): never
{
    http_response_code($status);
    if ($json) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
    } else {
        echo $error;
    }
    exit;
}

function requireLogin(bool $json = true): void
{
    if (!isset($_SESSION['user'])) {
        denyRequest(401, 'unauthorized', $json);
    }
}

function requirePermission(PDO $pdo, string $permission, bool $json = true): array
{
    requireLogin($json);
    $role = currentRole($pdo);
    if (!$role || !hasPermission($role, $permission)) {
        denyRequest(403, 'forbidden', $json);
    }
    return $role;
}

function roleByUserId(PDO $pdo, int $userId, bool $forUpdate = false): array
{
    $columns = implode(', ', array_map(static fn(string $column): string => "r.$column", ROLE_PERMISSION_COLUMNS));
    $sql = "SELECT u.id AS user_id, u.role_id, $columns FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?";
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function canManageUser(array $actorRole, array $targetRole): bool
{
    if (!$actorRole || !$targetRole || !hasPermission($actorRole, 'account_flg')) {
        return false;
    }
    if ((int)$actorRole['user_id'] === (int)$targetRole['user_id']) {
        return false;
    }
    if (isSystemRole($actorRole)) {
        return true;
    }
    return (int)($targetRole['account_flg'] ?? 0) !== 1;
}

function canEditUserPermissions(array $actorRole, array $targetRole): bool
{
    if (!$actorRole || !$targetRole || !hasPermission($actorRole, 'account_flg')) {
        return false;
    }
    if (isSystemRole($actorRole)) {
        return true;
    }
    if ((int)$actorRole['user_id'] === (int)$targetRole['user_id']) {
        return false;
    }
    return (int)($targetRole['account_flg'] ?? 0) !== 1;
}

function normalizedPermissionFlags(array $input): array
{
    $flags = [];
    foreach (ROLE_PERMISSION_COLUMNS as $flag) {
        $flags[$flag] = isset($input[$flag]) && (string)$input[$flag] === '1' ? 1 : 0;
    }
    return $flags;
}

function insertDedicatedRole(PDO $pdo, array $flags, string $auditUser, string $functionId): int
{
    $values = array_map(
        static fn(string $flag): int => (int)($flags[$flag] ?? 0),
        ROLE_PERMISSION_COLUMNS
    );
    $hasAppendDatetime = (bool)$pdo->query("SHOW COLUMNS FROM roles LIKE 'append_datetime'")->fetch(PDO::FETCH_ASSOC);
    $appendDatetimeColumn = $hasAppendDatetime ? ', append_datetime' : '';
    $appendDatetimeValue = $hasAppendDatetime ? ', NOW()' : '';
    $sql = "INSERT INTO roles (system_flg, create_account_flg, account_flg, update_confirmation_flg, practice_flg, game_flg, event_flg{$appendDatetimeColumn}, append_user_id, append_func_id, update_datetime, update_user_id, update_func_id, lock_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?{$appendDatetimeValue}, ?, ?, NOW(), ?, ?, CURRENT_TIMESTAMP)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...$values, $auditUser, $functionId, $auditUser, $functionId]);
    return (int)$pdo->lastInsertId();
}

function canAssignRole(array $actorRole, array $newRole): bool
{
    $canAssign = hasPermission($actorRole, 'account_flg') || hasPermission($actorRole, 'create_account_flg');
    if (!$actorRole || !$newRole || !$canAssign) {
        return false;
    }
    return isSystemRole($actorRole)
        || ((int)($newRole['system_flg'] ?? 0) !== 1 && (int)($newRole['account_flg'] ?? 0) !== 1);
}
