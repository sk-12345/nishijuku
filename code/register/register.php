<?php
session_start();
require_once '../db.php';

/* ========= ログイン必須 ========= */
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit;
}

$myRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

/* ========= 権限チェック ========= */
if (!in_array($myRoleId, [1, 2], true)) {
    exit('このページにアクセスする権限がありません');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規アカウント作成</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        <?php if ($myRoleId === 1): ?>
            <!-- SYSTEM は ADMIN / PHOTO / GENERAL 作成可能（SYSTEMは事故防止で作らせない） -->
            <option value="2">ADMIN</option>
            <option value="3">PHOTO</option>
            <option value="4">GENERAL</option>

        <?php elseif ($myRoleId === 2): ?>
            <!-- ADMIN は PHOTO / GENERAL のみ -->
            <option value="3">PHOTO</option>
            <option value="4">GENERAL</option>
        <?php endif; ?>

    </select>

    <button type="submit">作成</button>

</form>

<a href="../home/home.php">← ホームへ戻る</a>

</div>
</div>

</body>
</html>
