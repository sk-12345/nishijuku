<?php
session_start();
require_once '../db.php';

// ログイン必須
if (!isset($_SESSION['user']['id'])) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$new2    = $_POST['new_password_confirm'] ?? ''; // 確認用がある場合

// 空チェック
if ($current === '' || $new === '') {
    exit('入力が不足しています');
}

// 確認用があるなら一致チェック（フォームに無いならこのifは消してOK）
if ($new2 !== '' && $new !== $new2) {
    exit('新しいパスワードが一致しません');
}

// 現在のハッシュ取得
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('ユーザーが見つかりません');
}

// ✅ ここが重要：現在パスワード照合
if (!password_verify($current, $user['password_hash'])) {
    echo "現在のパスワードが間違っています<br>";
    echo '<a href="password_change.php">戻る</a>';
    exit;
}

// ✅ 新パスをハッシュ化して保存
$new_hash = password_hash($new, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$new_hash, $user_id]);

echo "パスワード変更完了<br>";
echo '<a href="../home/home.php">戻る</a>';
