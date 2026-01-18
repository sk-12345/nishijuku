<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=UTF-8');

// セッション確認
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$myId   = (int)($_SESSION['user']['id'] ?? 0);
$myRole = (int)($_SESSION['user']['role_id'] ?? 0); // 1=SYSTEM, 2=ADMIN, 3=PHOTO, 4=GENERAL

// 写真ユーザーのみ権限移行できる
if ($myRole !== 3) { // PHOTOのみ
    http_response_code(403);
    echo json_encode(['error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$targetId  = (int)($data['userId'] ?? 0);
$newRole   = (string)($data['newRole'] ?? '');

if ($targetId <= 0 || $newRole === '') {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_params'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 対象ユーザーを取得
$st = $pdo->prepare("SELECT id, role_id FROM users WHERE id = ?");
$st->execute([$targetId]);
$target = $st->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    http_response_code(404);
    echo json_encode(['error' => 'user_not_found'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 権限変更が可能か確認（写真ユーザーのみ）
if ($myId === $targetId || $target['role_id'] !== 4) {
    http_response_code(403);
    echo json_encode(['error' => 'cannot_change_this_user'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 新しい役職を数値に変換
$roleMap = ['USER' => 4, 'PHOTO' => 3];
$newRoleId = $roleMap[$newRole] ?? 4; // 新しい役職ID（デフォルトは一般ユーザー）

// 現在の役職を変更（写真ユーザーの場合、一般ユーザーに変更）
$currentRoleId = $target['role_id'];
if ($currentRoleId == 3) {
    // 既存の写真ユーザーを一般ユーザーに変更
    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([4, $myId]); // 元の写真ユーザーの役職を一般ユーザーに変更
}

// 権限移行先の役職を変更（一般ユーザーを写真ユーザーに変更）
$stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
$stmt->execute([$newRoleId, $targetId]);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
