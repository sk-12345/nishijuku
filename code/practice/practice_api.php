<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/practices/';


/* 投稿取得 */

$stmt = $pdo->query("
SELECT practices.*, append_datetime AS created_at
FROM practices
ORDER BY append_datetime DESC
");

$practices = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* 各投稿の画像取得 */

foreach ($practices as &$p) {

    $stmt2 = $pdo->prepare("
        SELECT image_path, description
        FROM practice_images
        WHERE practice_id = ?
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
    $practices,
    JSON_UNESCAPED_UNICODE
);
