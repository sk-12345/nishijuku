<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';


header('Content-Type: application/json; charset=UTF-8');

// ✅ ログイン必須
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$myId   = (int)($_SESSION['user']['id'] ?? 0);
$myRole = (int)($_SESSION['user']['role_id'] ?? 0); // 1=SYSTEM, 2=ADMIN, 3=PHOTO, 4=GENERAL（←統一）

// ✅ SYSTEM / ADMIN 以外は見れない
if (!in_array($myRole, [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ✅ roles
    $rolesStmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY id");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ users + roles
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.login_id,
            u.name,
            u.role_id,
            r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.id
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ 自分の権限で「変更先の候補」を絞る
function getSelectableRoleIds(int $myRole): array {
    if ($myRole === 1) return [2, 3, 4]; // SYSTEM：変更先にSYSTEMは出さない
    if ($myRole === 2) return [3, 4];    // ADMIN：PHOTO/GENERAL のみ
    return [];
}

// ✅ 「この相手の権限を変更できるか」判定
function canChangeRole(int $myRole, int $myId, array $targetUser): bool {
    $targetId   = (int)($targetUser['id'] ?? 0);
    $targetRole = (int)($targetUser['role_id'] ?? 0);

    if ($myId === $targetId) return false;

    if ($myRole === 1) return $targetRole !== 1;                // SYSTEMはSYSTEM以外OK
    if ($myRole === 2) return in_array($targetRole, [3, 4], true); // ADMINはPHOTO/GENERALだけ

    return false;
}

// ✅ 「削除できるか」判定
function canDeleteUser(int $myRole, int $myId, array $targetUser): bool {
    $targetId   = (int)($targetUser['id'] ?? 0);
    $targetRole = (int)($targetUser['role_id'] ?? 0);

    if ($myId === $targetId) return false;

    if ($myRole === 1) return $targetRole !== 1;
    if ($myRole === 2) return in_array($targetRole, [3, 4], true);

    return false;
}

$selectableIds = getSelectableRoleIds($myRole);

// フロント用フラグ付与
foreach ($users as &$u) {
    $u['can_change'] = canChangeRole($myRole, $myId, $u);
    $u['can_delete'] = canDeleteUser($myRole, $myId, $u);
}
unset($u);

try {
    echo json_encode([
        'me' => [
            'id' => $myId,
            'role_id' => $myRole,
            'selectable_role_ids' => $selectableIds
        ],
        'roles' => $roles,
        'users' => $users
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'json_error'], JSON_UNESCAPED_UNICODE);
}
