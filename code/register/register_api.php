<?php
session_start();
require_once __DIR__ . '/../db.php'; // ← $pdo が入ってるファイルに合わせてパス調整
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

// ✅ rolesテーブルから role_name を取得
function getRoleName(PDO $pdo, int $roleId): ?string {
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ? LIMIT 1");
    $stmt->execute([$roleId]);
    $name = $stmt->fetchColumn();
    return $name !== false ? (string)$name : null;
}

// SYSTEM: ADMIN/PHOTO/GENERAL
if ($myRoleId === 1) {
    $selectable = [
        ['id' => 2, 'name' => getRoleName($pdo, 2) ?: 'ADMIN'],
        ['id' => 3, 'name' => getRoleName($pdo, 3) ?: 'PHOTO'],
        ['id' => 4, 'name' => getRoleName($pdo, 4) ?: 'GENERAL'],
    ];
}
// ADMIN: PHOTO/GENERAL
else {
    $selectable = [
        ['id' => 3, 'name' => getRoleName($pdo, 3) ?: 'PHOTO'],
        ['id' => 4, 'name' => getRoleName($pdo, 4) ?: 'GENERAL'],
    ];
}

echo json_encode([
    'me' => ['role_id' => $myRoleId],
    'selectable_roles' => $selectable
], JSON_UNESCAPED_UNICODE);
