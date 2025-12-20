<?php
session_start();
require_once '../db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "
SELECT 
    u.id,
    u.login_id,
    u.password_hash,
    u.name,
    u.role_id,
    r.role_name
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.login_id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ ここが超重要：=== じゃなく password_verify()
if ($user && password_verify($password, $user['password_hash'])) {

    $_SESSION['user'] = [
        'id'       => $user['id'],
        'login_id' => $user['login_id'],
        'fullname' => $user['name'],
        'role'     => strtoupper($user['role_name']),
        'role_id'  => (int)$user['role_id'],
    ];

    header("Location: ../home/home.php");
    exit;

} else {
    // 失敗時はログイン画面へ戻す（エラー表示用）
    header("Location: ../login/login.php?err=1");
    exit;
}
