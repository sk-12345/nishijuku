<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') denyRequest(405, 'POSTで実行してください', false);
$actor = requirePermission($pdo, 'create_account_flg', false);
$loginId = trim((string)($_POST['login_id'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($loginId === '' || $name === '' || $password === '') denyRequest(400, '入力が不足しています', false);
$flags = normalizedPermissionFlags($_POST);
if (!isSystemRole($actor)) $flags['system_flg'] = 0;
$hash = password_hash($password, PASSWORD_DEFAULT);
$auditUser = (string)($_SESSION['user']['login_id'] ?? $_SESSION['user']['id'] ?? 'system');
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE login_id=?');
    $stmt->execute([$loginId]);
    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        denyRequest(409, 'そのログインIDはすでに使用されています', false);
    }
    $roleId = insertDedicatedRole($pdo, $flags, $auditUser, 'register');
    $stmt = $pdo->prepare("INSERT INTO users (login_id, password_hash, name, role_id, append_datetime, append_user_id, append_func_id, update_datetime, update_user_id, update_func_id, lock_timestamp) VALUES (?, ?, ?, ?, NOW(), ?, 'register', NOW(), ?, 'register', CURRENT_TIMESTAMP)");
    $stmt->execute([$loginId, $hash, $name, $roleId, $auditUser, $auditUser]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    denyRequest(500, 'アカウント作成に失敗しました', false);
}
header('Location: ../home/home.html');
exit;
