<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

// ここは画面だけ。処理は password_change_process.php 側でやる想定。
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パスワード変更</title>
    <link rel="stylesheet" href="password.css">
</head>
<body>

<div class="password-wrapper">
    <div class="password-box">
        <h2>パスワード変更</h2>

        <form action="password_change_process.php" method="POST">
            <label>現在のパスワード</label>
            <input type="password" name="current_password" required>

            <label>新しいパスワード</label>
            <input type="password" name="new_password" required minlength="6">

            <label>新しいパスワード（確認）</label>
            <input type="password" name="new_password_confirm" required minlength="6">

            <button type="submit">変更する</button>
        </form>

        <a href="../home/home.php" class="password-back-btn">← ホームへ戻る</a>
    </div>
</div>

</body>
</html>
