<?php
declare(strict_types=1);

session_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

function jexit(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if (!isset($_SESSION['user'])) {
  jexit(['ok' => false, 'error' => 'unauthorized'], 401);
}

// 毎回更新してOK（ワンタイムに近くなる）
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

jexit(['ok' => true, 'csrf_token' => $_SESSION['csrf_token']]);
