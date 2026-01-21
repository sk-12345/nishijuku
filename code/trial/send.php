<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

function jexit(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  jexit(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

// ---- config 読み込み（trialフォルダ内）----
$config = require __DIR__ . '/config.php';
$GMAIL_USER = (string)($config['GMAIL_USER'] ?? '');
$GMAIL_APP  = (string)($config['GMAIL_APP'] ?? '');
$TO_MAIL    = (string)($config['TO_MAIL'] ?? '');
$FROM_NAME  = (string)($config['FROM_NAME'] ?? '西塾柔道クラブ');

if ($GMAIL_USER === '' || $GMAIL_APP === '' || $TO_MAIL === '') {
  jexit(['ok' => false, 'error' => 'config_missing'], 500);
}

// ---- PHPMailer 読み込み（Composer不要）----
require_once __DIR__ . '/../../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---- 入力取得（保護者）----
$guardian = trim((string)($_POST['guardian_name'] ?? ''));
$phone    = trim((string)($_POST['phone'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$pref     = trim((string)($_POST['preferred_date'] ?? ''));
$message  = trim((string)($_POST['message'] ?? ''));

// ---- 参加者配列 ----
$participants = $_POST['participants'] ?? null;
if (!is_array($participants)) $participants = [];

// ---- バリデーション ----
if ($guardian === '' || $phone === '' || $email === '') {
  jexit(['ok' => false, 'error' => 'required_missing'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  jexit(['ok' => false, 'error' => 'email_invalid'], 400);
}
if (mb_strlen($guardian) > 100 || mb_strlen($phone) > 30 || mb_strlen($email) > 255) {
  jexit(['ok' => false, 'error' => 'too_long'], 400);
}
if (mb_strlen($pref) > 50 || mb_strlen($message) > 2000) {
  jexit(['ok' => false, 'error' => 'too_long'], 400);
}
if (count($participants) < 1) {
  jexit(['ok' => false, 'error' => 'participants_missing'], 400);
}

// 参加者の中身チェック（1..nのキーを想定）
$clean = [];
foreach ($participants as $idx => $p) {
  if (!is_array($p)) continue;

  $name = trim((string)($p['name'] ?? ''));
  $cat  = trim((string)($p['category'] ?? ''));
  $grade = trim((string)($p['grade'] ?? ''));
  $gender = trim((string)($p['gender'] ?? ''));
  $exp = trim((string)($p['experience'] ?? ''));
  $years = trim((string)($p['exp_years'] ?? ''));

  if ($name === '' || $cat === '' || $grade === '' || $exp === '') {
    jexit(['ok' => false, 'error' => 'participant_required_missing'], 400);
  }
  if (!in_array($cat, ['幼児','小学生','中学生'], true)) {
    jexit(['ok' => false, 'error' => 'participant_category_invalid'], 400);
  }
  if ($gender !== '' && !in_array($gender, ['男','女'], true)) {
    jexit(['ok' => false, 'error' => 'participant_gender_invalid'], 400);
  }
  if (!in_array($exp, ['未経験','経験あり'], true)) {
    jexit(['ok' => false, 'error' => 'participant_experience_invalid'], 400);
  }

  $clean[] = [
    'name' => $name,
    'category' => $cat,
    'grade' => $grade,
    'gender' => $gender,
    'experience' => $exp,
    'exp_years' => $years,
  ];
}

if (count($clean) < 1) {
  jexit(['ok' => false, 'error' => 'participants_missing'], 400);
}

// ---- 件名/本文整形 ----
$subject = '【西塾体験応募】' . $guardian . ' 様（' . count($clean) . '名）';

$body =
"西塾柔道クラブ 体験応募フォーム\n".
"----------------------------------\n".
"【保護者情報】\n".
"保護者氏名: {$guardian}\n".
"電話番号: {$phone}\n".
"メール: {$email}\n".
"希望日: " . ($pref !== '' ? $pref : '（未入力）') . "\n\n";

$body .= "【参加者】\n";
$no = 1;
foreach ($clean as $p) {
  $body .= "--- 参加者{$no} ---\n";
  $body .= "名前: {$p['name']}\n";
  $body .= "区分: {$p['category']}\n";
  $body .= "学年: {$p['grade']}\n";
  $body .= "性別: " . ($p['gender'] !== '' ? $p['gender'] : '（未回答）') . "\n";
  $body .= "経験: {$p['experience']}\n";
  $body .= "経験年数: " . ($p['exp_years'] !== '' ? $p['exp_years'] : '（未入力）') . "\n\n";
  $no++;
}

$body .= "【備考（全体）】\n" . ($message !== '' ? $message : '（未入力）') . "\n";
$body .= "----------------------------------\n";
$body .= "送信元IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$body .= "送信日時: " . date('Y-m-d H:i:s') . "\n";

try {
  $mail = new PHPMailer(true);
  $mail->CharSet = 'UTF-8';

  // デバッグ必要なら 2（普段は0）
  $mail->SMTPDebug = 0;
  $mail->Debugoutput = function($str, $level) {
    error_log("SMTP[$level] $str");
  };

  // Gmail SMTP（安定の465/SMTPS）
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->AuthType = 'LOGIN';
  $mail->Username = $GMAIL_USER;
  $mail->Password = $GMAIL_APP;

  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port = 465;

  $mail->setFrom($GMAIL_USER, $FROM_NAME);
  $mail->addAddress($TO_MAIL);

  // フォームのメールを返信先に（返信が楽）
  $mail->addReplyTo($email, $guardian);

  $mail->Subject = $subject;
  $mail->Body = $body;

  $mail->send();
  jexit(['ok' => true]);

} catch (Exception $e) {
  jexit([
    'ok' => false,
    'error' => 'send_failed',
    'detail' => $e->getMessage()
  ], 500);
}
