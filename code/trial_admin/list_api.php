<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
session_start();

function jexit(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user'])) {
  jexit(['ok' => false, 'error' => 'unauthorized'], 401);
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if (!in_array($roleId, [1, 2], true)) { // 1=SYSTEM, 2=ADMIN
  jexit(['ok' => false, 'error' => 'forbidden'], 403);
}

require_once __DIR__ . '/../db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  jexit(['ok' => false, 'error' => '$pdoが未生成です'], 500);
}

$stmt = $pdo->query("
  SELECT id, name, phone, email, age, preferred_date, message, created_at
  FROM trial_applications
  ORDER BY created_at DESC, id DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

jexit(['ok' => true, 'rows' => $rows]);
