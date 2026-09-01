<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    denyRequest(405, 'POSTで実行してください', false);
}

$actor = requirePermission($pdo, 'account_flg', false);
$targetId = (int)($_POST['user_id'] ?? 0);
if ($targetId <= 0) {
    denyRequest(400, '対象ユーザーが不正です', false);
}

$flags = normalizedPermissionFlags($_POST);

$auditUser = (string)($_SESSION['user']['login_id'] ?? $actor['user_id']);
$pdo->beginTransaction();
try {
    $target = roleByUserId($pdo, $targetId, true);
    if (!$target) {
        $pdo->rollBack();
        denyRequest(404, '対象ユーザーが存在しません', false);
    }
    if (!canEditUserPermissions($actor, $target)) {
        $pdo->rollBack();
        denyRequest(403, 'このユーザーの権限は変更できません', false);
    }

    // system_flg はシステムユーザー以外から送られた値を信用しない。
    if (!isSystemRole($actor)) {
        $flags['system_flg'] = (int)$target['system_flg'];
    }

    $values = array_map(static fn(string $flag): int => $flags[$flag], ROLE_PERMISSION_COLUMNS);
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id=?');
    $countStmt->execute([(int)$target['role_id']]);

    if ((int)$countStmt->fetchColumn() > 1) {
        $newRoleId = insertDedicatedRole($pdo, $flags, $auditUser, 'account_permission_update');
        $stmt = $pdo->prepare("UPDATE users SET role_id=?, update_datetime=NOW(), update_user_id=?, update_func_id='account_permission_update', lock_timestamp=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->execute([$newRoleId, $auditUser, $targetId]);
    } else {
        $stmt = $pdo->prepare("UPDATE roles SET system_flg=?, create_account_flg=?, account_flg=?, update_confirmation_flg=?, practice_flg=?, game_flg=?, event_flg=?, update_datetime=NOW(), update_user_id=?, update_func_id='account_permission_update', lock_timestamp=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->execute([...$values, $auditUser, (int)$target['role_id']]);
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    denyRequest(500, '権限の更新に失敗しました', false);
}

header('Location: account.html');
exit;
