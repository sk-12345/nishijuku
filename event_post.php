<?php
session_start();
require_once 'db.php';

// ✅ ログイン必須
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user']['role'];
$user_id = $_SESSION['user']['id'];

// =========================
// ✅ 削除処理（※ 全ログインユーザーOK）
// =========================
if (isset($_POST['delete_id'])) {

    $delete_id = $_POST['delete_id'];

    // 画像パス取得
    $stmt = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
    $stmt->execute([$delete_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        // ✅ 画像削除
        if (file_exists($event['image_path'])) {
            unlink($event['image_path']);
        }

        // ✅ DB削除
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$delete_id]);
    }

    header("Location: event_post.php");
    exit;
}

// =========================
// ✅ 追加処理（※ ADMIN / SYSTEM のみ）
// =========================
if (isset($_POST['title'])) {

    // ✅ 権限チェック
    if ($role !== 'SYSTEM' && $role !== 'ADMIN') {
        exit('投稿権限がありません');
    }

    $title = $_POST['title'];
    $description = $_POST['description'];

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $image_path = $upload_dir . $image_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {

        $stmt = $pdo->prepare(
            "INSERT INTO events (title, description, image_path, created_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$title, $description, $image_path, $user_id]);

        header("Location: event_post.php");
        exit;
    } else {
        echo "画像アップロード失敗";
    }
}

// =========================
// ✅ 一覧取得
// =========================
$stmt = $pdo->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>イベント管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ✅ デザイン用CSS -->
    <link rel="stylesheet" href="event_post.css">
</head>
<body>

<div class="event-wrapper">

    <h2 class="page-title">イベント管理（ログイン必須）</h2>

    <!-- ✅ 追加フォーム（ADMIN / SYSTEM のみ表示） -->
    <?php if ($role === 'SYSTEM' || $role === 'ADMIN'): ?>
        <div class="form-box">
            <form method="POST" enctype="multipart/form-data">

                <label>タイトル</label>
                <input type="text" name="title" required>

                <label>説明</label>
                <textarea name="description" rows="5" required></textarea>

                <label>写真</label>
                <input type="file" name="image" accept="image/*" required>

                <button type="submit" class="post-btn">投稿</button>

            </form>
        </div>
    <?php endif; ?>

    <h2 class="page-title">イベント一覧</h2>

    <div class="event-grid">
        <?php foreach ($events as $event): ?>
            <div class="event-card">

                <h3><?= htmlspecialchars($event['title']) ?></h3>

                <img src="<?= htmlspecialchars($event['image_path']) ?>">

                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                <small>投稿日：<?= $event['created_at'] ?></small>

                <!-- ✅ 削除ボタン（全ログインユーザーOK） -->
                <form method="POST" onsubmit="return confirm('削除しますか？');">
                    <input type="hidden" name="delete_id" value="<?= $event['id'] ?>">
                    <button type="submit" class="delete-btn">削除</button>
                </form>

            </div>
        <?php endforeach; ?>
    </div>

    <div class="back-links">
        <a href="home.php">ホームに戻る</a>
        <a href="event.php">一般公開ページへ</a>
    </div>

</div>

</body>
</html>
