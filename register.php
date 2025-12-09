<?php
session_start();
require_once 'db.php';

// ✅ ログインチェック
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user']['role'];

// ✅ SYSTEM / ADMIN 以外は入れない
if ($role !== 'SYSTEM' && $role !== 'ADMIN') {
    exit('このページにアクセスする権限がありません');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規アカウント作成</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ✅ 専用CSS -->
    <link rel="stylesheet" href="register.css">
</head>
<body>

<div class="register-wrapper">

    <div class="register-box">

        <h2>新規アカウント作成</h2>

        <form action="register_process.php" method="POST">

            <label>ログインID</label>
            <input type="text" name="login_id" required>

            <label>名前</label>
            <input type="text" name="name" required>

            <label>パスワード</label>
            <input type="password" name="password" required>

            <label>権限</label>
            <select name="role_id" required>

                <?php if ($role === 'SYSTEM'): ?>
                    <!-- ✅ SYSTEM は全部作れる -->
                    <option value="1">SYSTEM</option>
                    <option value="2">ADMIN</option>
                    <option value="3">GENERAL</option>
                    <option value="4">PHOTE</option>

                <?php elseif ($role === 'ADMIN'): ?>
                    <!-- ✅ ADMIN は GENERAL / PHOTE のみ -->
                    <option value="3">GENERAL</option>
                    <option value="4">PHOTE</option>
                <?php endif; ?>

            </select>

            <button type="submit">作成</button>

        </form>

        <a href="home.php" class="back-btn">← ホームへ戻る</a>

    </div>

</div>

</body>
</html>
