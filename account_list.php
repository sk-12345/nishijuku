<?php
session_start();
require_once 'db.php';

// ✅ ログイン必須
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$myId   = $_SESSION['user']['id'];     // ✅ 自分のID
$myRole = $_SESSION['user']['role'];   // SYSTEM / ADMIN / GENERAL / PHOTO

// ✅ SYSTEM / ADMIN 以外は見れない
if ($myRole !== 'SYSTEM' && $myRole !== 'ADMIN') {
    exit('このページを閲覧する権限がありません');
}

// ✅ users + roles を結合して取得
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.login_id,
        u.name,
        r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アカウント管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="account.css">
</head>
<body>

<div class="account-wrapper">

    <h2>アカウント管理</h2>

    <table class="account-table">
        <tr>
            <th>ID</th>
            <th>ログインID</th>
            <th>名前</th>
            <th>権限</th>
            <th>操作</th>
        </tr>

        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['login_id']) ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= $user['role_name'] ?></td>
            <td>

                <?php
                // ✅ 削除できるか最終判定
                $canDelete = false;

                // ✅ 自分自身は削除不可
                if ($myId != $user['id']) {

                    // ✅ SYSTEM は SYSTEM 以外なら削除可
                    if ($myRole === 'SYSTEM' && $user['role_name'] !== 'SYSTEM') {
                        $canDelete = true;
                    }

                    // ✅ ADMIN は GENERAL / PHOTO のみ削除可
                    if (
                        $myRole === 'ADMIN' &&
                        ($user['role_name'] === 'GENERAL' || $user['role_name'] === 'PHOTO')
                    ) {
                        $canDelete = true;
                    }
                }
                ?>

                <?php if ($canDelete): ?>
                    <form method="POST" action="account_delete.php" onsubmit="return confirm('このアカウントを削除しますか？');">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button class="delete-btn">削除</button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>

            </td>
        </tr>
        <?php endforeach; ?>

    </table>

    <p class="back"><a href="home.php">← ホームに戻る</a></p>

</div>

</body>
</html>
