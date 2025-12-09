<?php
session_start();
require_once 'db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "
SELECT 
    users.id,
    users.login_id,
    users.password_hash,
    users.name,
    roles.role_name
FROM users
INNER JOIN roles ON users.role_id = roles.id
WHERE users.login_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password === $user['password_hash']) {

    // ✅ セッションに必要な情報だけ保存
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'login_id' => $user['login_id'],
        'fullname' => $user['name'],
        'role'     => $user['role_name']
    ];

    header("Location: home.php");
    exit;

} else {
    echo "ログイン失敗";
}
