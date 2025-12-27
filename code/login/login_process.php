<?php
session_start();
require_once __DIR__ . '/../db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "
SELECT 
    u.id,
    u.login_id,
    u.password_hash,
    u.name,
    u.role_id
FROM users u
WHERE u.login_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {

    $_SESSION['user'] = [
        'id'       => (int)$user['id'],
        'login_id' => $user['login_id'],
        'fullname' => $user['name'],
        'role_id'  => (int)$user['role_id'],
    ];

    header("Location: /nishijuku/code/home/home.php");
    exit;

} else {
    echo "ログイン失敗";
}
