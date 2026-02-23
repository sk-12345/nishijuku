<?php
$db_host = '127.0.0.1';
$db_port = '3308';
$db_name = 'nishijuku_nishijuku';
$db_user = 'nishijuku_nishijuku';
$db_pass = 'kmkr3110';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo 'DB接続エラー: ' . $e->getMessage();
    exit;
}

// PowerShellでMySQLに接続するためのコマンド
// ssh -N -L 3308:mysql80.nishijuku.sakura.ne.jp:3306 nishijuku@nishijuku.sakura.ne.jp