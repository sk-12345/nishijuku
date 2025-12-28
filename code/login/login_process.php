<?php
session_start();
require_once __DIR__ . '/../db.php';

// POST値取得
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 未入力対策
if ($username === '' || $password === '') {
    header("Location: login.html?err=1");
    exit;
}

// ユーザー取得
$sql = "
SELECT 
    id,
    login_id,
    password_hash,
    name,
    role_id
FROM users
WHERE login_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 認証
if ($user && password_verify($password, $user['password_hash'])) {

    // ✅ セッション固定攻撃対策（超重要）
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => (int)$user['id'],
        'login_id' => $user['login_id'],
        'fullname' => $user['name'],
        'role_id'  => (int)$user['role_id'],
    ];

    // 成功 → ホーム（HTML）
    header("Location: ../home/home.html");
    exit;

} else {
    // 失敗 → ログイン画面へ
    header("Location: login.html?err=1");
    exit;
}
