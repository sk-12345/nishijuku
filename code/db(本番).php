<?php
$db_host = 'mysql3113.db.sakura.ne.jp';
$db_name = 'nishijuku_nishijuku';
$db_user = 'nishijuku_nishijuku';
$db_pass = 'kmkr3110';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo 'DBÚ‘±ƒGƒ‰[: ' . $e->getMessage();
    exit;
}