<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user']['id'];
$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $current === $user['password_hash']) {

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$new, $user_id]);

    echo "パスワード変更完了<br>";
    echo '<a href="home.php">戻る</a>';

} else {
    echo "現在のパスワードが間違っています<br>";
    echo '<a href="password_change.php">戻る</a>';
}
