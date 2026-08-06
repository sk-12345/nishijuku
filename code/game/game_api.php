<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/games/';


/* 投稿取得 */

$stmt = $pdo->query("
SELECT games.*, append_datetime AS created_at
FROM games
ORDER BY append_datetime DESC
");

$games = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* 各投稿の画像取得 */

foreach ($games as &$p) {

    $stmt2 = $pdo->prepare("
        SELECT image_path, description
        FROM game_images
        WHERE game_id = ?
        ORDER BY display_order ASC, id ASC
    ");

    $stmt2->execute([$p['id']]);

    $imgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $p['images'] = [];

    foreach ($imgs as $img) {

        $p['images'][] = [
            'image'   => $UPLOAD_DIR_URL . $img['image_path'],
            'comment' => $img['description']
        ];

    }

}


echo json_encode(
    $games,
    JSON_UNESCAPED_UNICODE
);
