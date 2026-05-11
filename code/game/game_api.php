<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/games/';

$stmt = $pdo->query("
    SELECT
        id,
        title,
        description,
        create_by,
        DATE_FORMAT(create_at, '%Y/%m/%d %H:%i') AS created_at
    FROM games
    ORDER BY create_at DESC
");

$games = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($games as &$p) {

    $stmt2 = $pdo->prepare("
        SELECT
            id,
            image_path,
            description,
            display_order
        FROM game_images
        WHERE game_id = ?
        ORDER BY display_order ASC, id ASC
    ");

    $stmt2->execute([$p['id']]);

    $imgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $p['images'] = [];

    foreach ($imgs as $img) {
        $p['images'][] = [
            'id' => $img['id'],
            'image' => $UPLOAD_DIR_URL . $img['image_path'],
            'comment' => $img['description'],
            'display_order' => $img['display_order']
        ];
    }
}

echo json_encode(
    $games,
    JSON_UNESCAPED_UNICODE
);