<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

$user = $_SESSION['user'];

// ✅ role_id を数字で扱う
$roleId = isset($user['role_id']) ? (int)$user['role_id'] : 0;

// ✅ 表示用（数字→名称）
$roleNameMap = [
    1 => 'SYSTEM',
    2 => 'ADMIN',
    3 => 'PHOTO',
    4 => 'GENERAL',
];
$roleName = $roleNameMap[$roleId] ?? ('UNKNOWN(' . $roleId . ')');

// ✅ SYSTEM/ADMIN 判定（数字で）
$isAdminOrSystem = in_array($roleId, [1, 2], true);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ホーム | 西塾柔道クラブ</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

<div class="home-wrapper">
    <div class="home-box">

        <h2>
            ようこそ、<br>
            <?= htmlspecialchars($user['fullname'] ?? '') ?> さん
        </h2>

        <!-- ✅ 権限表示：role_id → 文字に変換して表示 -->
        <p class="role">権限：<?= htmlspecialchars($roleName) ?></p>

        <h3>メニュー</h3>

        <div class="card-menu">

            <?php if ($isAdminOrSystem): ?>
                <a href="../register/register.php" class="card">新規アカウント作成</a>
            <?php endif; ?>

            <?php if ($isAdminOrSystem): ?>
                <a href="../account/account_list.php" class="card">アカウント管理</a>
            <?php endif; ?>

            <a href="../password/password_change.php" class="card">パスワード変更</a>

            <a href="../event_post/event_post.php" class="card main-card">イベント一覧・投稿</a>

            <a href="../logout.php" class="card logout-card">ログアウト</a>

        </div>

    </div>
</div>

</body>
</html>
