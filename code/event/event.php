<?php
require_once '../db.php';

// =========================
// 画像URL（絶対パス）
// =========================
$UPLOAD_DIR_URL = '/nishijuku/img/uploads/';

$stmt = $pdo->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>イベント一覧 | 西塾柔道クラブ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="event.css">
</head>
<body>

<div class="event-wrapper">
    <h2 class="page-title">イベント一覧</h2>

    <div class="event-grid">
        <?php if (count($events) === 0): ?>
            <p class="no-event">現在、公開中のイベントはありません。</p>
        <?php endif; ?>

        <?php foreach ($events as $event): ?>
            <?php
                // DBには「ファイル名のみ」が入っている前提
                $imgSrc = $UPLOAD_DIR_URL . $event['image_path'];
            ?>

            <div class="event-card">
                <h3><?= htmlspecialchars($event['title']) ?></h3>

                <img
                    src="<?= htmlspecialchars($imgSrc) ?>"
                    alt="イベント画像"
                    onclick="openModal(
                        '<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($event['title'], ENT_QUOTES) ?>',
                        '<?= nl2br(htmlspecialchars($event['description'], ENT_QUOTES)) ?>'
                    )"
                >

                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                <small>投稿日：<?= htmlspecialchars($event['created_at']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="back-links">
        <a href="../index/index.html">ホームに戻る</a>
    </div>
</div>

<!-- モーダル -->
<div id="modal" class="modal" onclick="closeModal()">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h3 id="modal-title"></h3>
        <img id="modal-img">
        <p id="modal-text"></p>
        <button onclick="closeModal()">閉じる</button>
    </div>
</div>

<script>
function openModal(imgSrc, title, text) {
    document.getElementById("modal").style.display = "flex";
    document.getElementById("modal-img").src = imgSrc;
    document.getElementById("modal-title").innerHTML = title;
    document.getElementById("modal-text").innerHTML = text;
}
function closeModal() {
    document.getElementById("modal").style.display = "none";
}
</script>

</body>
</html>
