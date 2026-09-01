<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

header('Content-Type: application/json; charset=UTF-8');

// セッション確認
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$myId   = (int)($_SESSION['user']['id'] ?? 0);
$actorRole = requirePermission($pdo, 'account_flg');
$myRole = (int)$actorRole['role_id'];
$auditUser = (string)($_SESSION['user']['login_id'] ?? $myId);

// PHOTOのみ実行可能
if (!isSystemRole($actorRole)) {
    http_response_code(403);
    echo json_encode(['error' => 'use_account_management'], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * =========================
 * GET: 移行先ユーザー一覧取得（GENERALのみ）
 * =========================
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // ※ usersテーブルの「氏名」カラム名に合わせて変更してな
    // 例: name / username / full_name / user_name など
    $nameCol = 'name';

    // nameカラムが無い場合に備えて安全にSELECTを変える（失敗したらusernameへフォールバック）
    try {
        $sql = "SELECT id, {$nameCol} AS name, role_id FROM users WHERE role_id = 4 ORDER BY id ASC";
        $st = $pdo->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // フォールバック例（usernameがある想定）
        $sql = "SELECT id, username AS name, role_id FROM users WHERE role_id = 4 ORDER BY id ASC";
        $st = $pdo->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['ok' => true, 'users' => $rows], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * =========================
 * POST: PHOTO権限を移行（自分→GENERAL / 相手→PHOTO）
 * =========================
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json'], JSON_UNESCAPED_UNICODE);
    exit();
}

$targetId = (int)($data['userId'] ?? 0);

if ($targetId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_params'], JSON_UNESCAPED_UNICODE);
    exit();
}

// 自分自身には移行できない
if ($targetId === $myId) {
    http_response_code(403);
    echo json_encode(['error' => 'cannot_change_self'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $pdo->beginTransaction();

    // 対象ユーザーをロックして取得（同時実行対策）
    $st = $pdo->prepare("SELECT id, role_id FROM users WHERE id = ? FOR UPDATE");
    $st->execute([$targetId]);
    $target = $st->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'user_not_found'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 移行先は GENERAL のみ（SYSTEM/ADMIN/PHOTO へは渡さない）
    if ((int)$target['role_id'] !== 4) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'target_must_be_general'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 自分もロックして「今もPHOTOか」を最終確認（セッション改ざん/ズレ対策）
    $stMe = $pdo->prepare("SELECT id, role_id FROM users WHERE id = ? FOR UPDATE");
    $stMe->execute([$myId]);
    $me = $stMe->fetch(PDO::FETCH_ASSOC);

    if (!$me || (int)$me['role_id'] !== 3) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'your_role_is_not_photo_now'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ①自分を GENERAL に降格
    $stmt = $pdo->prepare("
        UPDATE users
        SET role_id = 4, update_datetime = NOW(), update_user_id = ?,
            update_func_id = 'photo_role_transfer', lock_timestamp = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$auditUser, $myId]);

    // ②相手を PHOTO に昇格
    $stmt = $pdo->prepare("
        UPDATE users
        SET role_id = 3, update_datetime = NOW(), update_user_id = ?,
            update_func_id = 'photo_role_transfer', lock_timestamp = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$auditUser, $targetId]);

    $stmt = $pdo->prepare("
        INSERT INTO role_transfer_logs (
            from_user_id, to_user_id, description, created_at,
            append_datetime, append_user_id, append_func_id,
            update_datetime, update_user_id, update_func_id, lock_timestamp
        )
        VALUES (?, ?, 'PHOTO role transfer', CURRENT_DATE,
                NOW(), ?, 'photo_role_transfer', NOW(), ?, 'photo_role_transfer', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$myId, $targetId, $auditUser, $auditUser]);

    $pdo->commit();

    // セッションも更新（これしないと画面上はPHOTOのままになったりする）
    $_SESSION['user']['role_id'] = 4;

    echo json_encode(['ok' => true, 'myRole' => 4, 'targetRole' => 3], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'server_error'], JSON_UNESCAPED_UNICODE);
}
