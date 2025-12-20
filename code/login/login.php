<?php
session_start();

// すでにログインしてたらホームへ
if (isset($_SESSION['user'])) {
    header("Location: ../home/home.php");
    exit;
}

// エラー表示用
$err = isset($_GET['err']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン | 西塾柔道クラブ</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">

        <h2>ログイン</h2>

        <?php if ($err): ?>
            <p class="error-msg">ユーザーIDまたはパスワードが違います</p>
        <?php endif; ?>

        <form action="login_process.php" method="POST">

            <label>ユーザーID</label>
            <input type="text" name="username" required>

            <label>パスワード</label>
            <input type="password" name="password" required>

            <button type="submit">ログイン</button>

            <a href="/nishijuku/code/index/index.html" class="back-btn">← 戻る</a>

        </form>

    </div>
</div>

</body>
</html>
