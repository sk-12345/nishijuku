<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <!-- ✅ vw / % を効かせるため必須 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ホーム | 西塾柔道クラブ</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

<div class="home-wrapper">

    <div class="home-box">

        <h2>
            ようこそ、<br>
            <?php echo htmlspecialchars($user['fullname']); ?> さん
        </h2>

        <p class="role">権限：<?php echo htmlspecialchars($user['role']); ?></p>

        <h3>メニュー</h3>

        <div class="card-menu">

            <?php if ($user['role'] === 'SYSTEM' || $user['role'] === 'ADMIN'): ?>
                <a href="register.php" class="card">新規アカウント作成</a>
            <?php endif; ?>

            <?php if ($user['role'] === 'SYSTEM' || $user['role'] === 'ADMIN'): ?>
                <a href="account_list.php" class="card">アカウント管理</a>
            <?php endif; ?>

            <a href="password_change.php" class="card">パスワード変更</a>

            <a href="event_post.php" class="card main-card">イベント一覧・投稿</a>

            <a href="logout.php" class="card logout-card">ログアウト</a>

        </div>

    </div>

</div>

</body>
</html>
