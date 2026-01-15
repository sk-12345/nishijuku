<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

function jexit(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// ★role_id はあなたのDBに合わせて調整してOK
const ROLE_SYSTEM  = 1;
const ROLE_ADMIN   = 2;
const ROLE_PHOTO   = 3;
const ROLE_GENERAL = 4;

if (!isset($_SESSION['user'])) {
  jexit(['ok' => false, 'error' => 'unauthorized', 'message' => 'ログインしてください'], 401);
}

$me = $_SESSION['user'];
$myId = (int)($me['id'] ?? 0);
$myRoleId = (int)($me['role_id'] ?? 0);

if ($myId <= 0) {
  jexit(['ok' => false, 'error' => 'invalid_session'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jexit(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

// 写真ユーザーのみ
if ($myRoleId !== ROLE_PHOTO) {
  jexit(['ok' => false, 'error' => 'forbidden', 'message' => '写真ユーザーのみ譲渡できます'], 403);
}

// CSRFチェック
$token = (string)($_POST['csrf_token'] ?? '');
if (!isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
  jexit(['ok' => false, 'error' => 'csrf', 'message' => '不正な操作です（CSRF）'], 400);
}

$toUserId = (int)($_POST['to_user_id'] ?? 0);
if ($toUserId <= 0) {
  jexit(['ok' => false, 'error' => 'bad_request', 'message' => '譲渡先が不正です'], 400);
}
if ($toUserId === $myId) {
  jexit(['ok' => false, 'error' => 'bad_request', 'message' => '自分には譲渡できません'], 400);
}

try {
  $pdo->beginTransaction();

  // 譲渡元をロックして最新状態確認
  $stmt = $pdo->prepare("SELECT id, role_id FROM users WHERE id = ? FOR UPDATE");
  $stmt->execute([$myId]);
  $from = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$from) {
    $pdo->rollBack();
    jexit(['ok' => false, 'error' => 'not_found', 'message' => '譲渡元が見つかりません'], 404);
  }
  if ((int)$from['role_id'] !== ROLE_PHOTO) {
    $pdo->rollBack();
    jexit(['ok' => false, 'error' => 'conflict', 'message' => 'あなたは既に写真ユーザーではありません'], 409);
  }

  // 譲渡先をロック
  $stmt->execute([$toUserId]);
  $to = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$to) {
    $pdo->rollBack();
    jexit(['ok' => false, 'error' => 'not_found', 'message' => '譲渡先が見つかりません'], 404);
  }

  // 譲渡先は一般ユーザー限定（事故防止）
  if ((int)$to['role_id'] !== ROLE_GENERAL) {
    $pdo->rollBack();
    jexit(['ok' => false, 'error' => 'conflict', 'message' => '譲渡先は一般ユーザーのみ指定できます'], 409);
  }

  // 入れ替え
  $upd = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
  $upd->execute([ROLE_GENERAL, $myId]);
  $upd->execute([ROLE_PHOTO,   $toUserId]);

  $pdo->commit();

  // 自分は一般ユーザーになったのでセッションも更新
  $_SESSION['user']['role_id'] = ROLE_GENERAL;

  // CSRFは使い捨てに近づける（任意）
  unset($_SESSION['csrf_token']);

  jexit(['ok' => true, 'message' => '写真権限を譲渡しました']);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  jexit(['ok' => false, 'error' => 'server_error', 'message' => '処理に失敗しました'], 500);
}
