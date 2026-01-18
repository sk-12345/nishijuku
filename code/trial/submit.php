<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

function jexit(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * ログイン不要：誰でも送れる（ここでは session_start しない）
 * ただし最低限のバリデーションはする
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jexit(['ok' => false, 'error' => 'method not allowed'], 405);
}

require_once __DIR__ . '/../db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  jexit(['ok' => false, 'error' => '$pdoが未生成です'], 500);
}

// 受け取り
$name  = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$age   = trim((string)($_POST['age'] ?? ''));
$pref  = trim((string)($_POST['preferred_date'] ?? ''));
$msg   = trim((string)($_POST['message'] ?? ''));

// 必須チェック
if ($name === '' || $phone === '' || $email === '') {
  jexit(['ok' => false, 'error' => '必須項目（お名前・電話番号・メール）を入力してください。'], 400);
}

// 長さ制限（DBに合わせる）
if (mb_strlen($name) > 100)  jexit(['ok' => false, 'error' => 'お名前が長すぎます。'], 400);
if (mb_strlen($phone) > 30)  jexit(['ok' => false, 'error' => '電話番号が長すぎます。'], 400);
if (mb_strlen($email) > 255) jexit(['ok' => false, 'error' => 'メールが長すぎます。'], 400);
if (mb_strlen($age) > 50)    jexit(['ok' => false, 'error' => '学年/年齢が長すぎます。'], 400);
if (mb_strlen($pref) > 50)   jexit(['ok' => false, 'error' => '希望日が長すぎます。'], 400);
if (mb_strlen($msg) > 2000)  jexit(['ok' => false, 'error' => '備考が長すぎます。'], 400);

// メール形式ざっくりチェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  jexit(['ok' => false, 'error' => 'メールアドレスの形式が正しくありません。'], 400);
}

// IP/UA（管理画面で見る用）
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
if (mb_strlen($ua) > 255) $ua = mb_substr($ua, 0, 255);

try {
  $stmt = $pdo->prepare("
    INSERT INTO trial_applications
      (name, phone, email, age, preferred_date, message, ip, user_agent)
    VALUES
      (:name, :phone, :email, :age, :preferred_date, :message, :ip, :ua)
  ");

  $stmt->execute([
    ':name' => $name,
    ':phone' => $phone,
    ':email' => $email,
    ':age' => ($age === '' ? null : $age),
    ':preferred_date' => ($pref === '' ? null : $pref),
    ':message' => ($msg === '' ? null : $msg),
    ':ip' => ($ip === '' ? null : $ip),
    ':ua' => ($ua === '' ? null : $ua),
  ]);

  jexit(['ok' => true]);
} catch (Throwable $e) {
  jexit(['ok' => false, 'error' => '保存に失敗しました。'], 500);
}
