<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/practices/';

$stmt = $pdo->query("
    SELECT
        id,
        title,
        description,
        created_by,
        DATE_FORMAT(created_at, '%Y/%m/%d %H:%i') AS created_at
    FROM practices
    ORDER BY created_at DESC
");

$practices = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($practices as &$p) {

    $stmt2 = $pdo->prepare("
        SELECT
            id,
            image_path,
            description,
            display_order
        FROM practice_images
        WHERE practice_id = ?
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
    $practices,
    JSON_UNESCAPED_UNICODE
);