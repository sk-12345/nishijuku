<?php
session_start();
require_once '../db.php';

// =========================
// ログイン必須
// =========================
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

// ✅ role_id で管理（数字で統一）
$role_id = (int)($_SESSION['user']['role_id'] ?? 0);
$user_id = (int)($_SESSION['user']['id'] ?? 0);

// =========================
// パス定義（超重要）
// =========================

// 物理パス（保存・削除用）
$UPLOAD_DIR_REAL = __DIR__ . '/../../img/uploads/';

// URLパス（表示用：絶対パス）
$UPLOAD_DIR_URL  = '/nishijuku/img/uploads/';

// フォルダがなければ作成
if (!is_dir($UPLOAD_DIR_REAL)) {
    mkdir($UPLOAD_DIR_REAL, 0777, true);
}

// =========================
// 削除処理（GENERAL=4 以外のみ）
// =========================
if (isset($_POST['delete_id'])) {

    // ✅ 一般ユーザー(4)は削除不可
    if ($role_id === 4) {
        exit('削除する権限がありません');
    }

    $delete_id = (int)$_POST['delete_id'];

    // 画像ファイル取得
    $stmt = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
    $stmt->execute([$delete_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        // image_path は「ファイル名」が入ってる想定
        $filename = basename($event['image_path']);
        $realPath = $UPLOAD_DIR_REAL . $filename;

        if (file_exists($realPath)) {
            unlink($realPath);
        }

        // DB削除
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$delete_id]);
    }

    header("Location: event_post.php");
    exit;
}

// =========================
// 追加処理（SYSTEM=1 / ADMIN=2 のみ）
// =========================
if (isset($_POST['title'])) {

    // ✅ SYSTEM(1) / ADMIN(2) 以外は投稿不可
    if (!in_array($role_id, [1, 2, 3], true)) {
        exit('投稿権限がありません');
    }

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $description === '') {
        exit('タイトルと説明は必須です');
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        exit('画像が選択されていません');
    }

    // 拡張子チェック（最低限）
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        exit('画像形式は jpg/jpeg/png/gif/webp のみ対応です');
    }

    // ファイル名生成
    $filename = uniqid('event_', true) . '.' . $ext;

    // 保存先
    $realPath = $UPLOAD_DIR_REAL . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $realPath)) {

        // DBには「ファイル名だけ」保存
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, image_path, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $description,
            $filename,
            $user_id
        ]);

        header("Location: event_post.php");
        exit;

    } else {
        exit("画像アップロード失敗");
    }
}

// =========================
// 一覧取得
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
    <link rel="stylesheet" href="event_post.css">
</head>
<body>

<div class="event-wrapper">

    <h2 class="page-title">イベント管理（ログイン必須）</h2>

    <!-- 投稿フォーム（SYSTEM=1 / ADMIN=2 のみ） -->
    <?php if (in_array($role_id, [1, 2, 3], true)): ?>
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

                <img
                    src="<?= $UPLOAD_DIR_URL . htmlspecialchars($event['image_path']) ?>"
                    alt="イベント画像"
                >

                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

                <small>投稿日：<?= htmlspecialchars($event['created_at']) ?></small>

                <!-- 削除ボタン（GENERAL=4 以外のみ表示） -->
                <?php if ($role_id !== 4): ?>
                    <form method="POST" onsubmit="return confirm('削除しますか？');">
                        <input type="hidden" name="delete_id" value="<?= (int)$event['id'] ?>">
                        <button type="submit" class="delete-btn">削除</button>
                    </form>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

    <div class="back-links">
        <a href="../home/home.php">ホームに戻る</a>
        <a href="../event/event.php">一般公開ページへ</a>
    </div>

</div>

</body>
</html>
