<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/events/';

$stmt = $pdo->query("
    SELECT
        id,
        title,
        description,
        created_by,
        DATE_FORMAT(created_at, '%Y/%m/%d %H:%i') AS created_at
    FROM events
    ORDER BY created_at DESC
");

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($events as &$e) {

    $stmt2 = $pdo->prepare("
        SELECT
            id,
            image_path,
            description,
            display_order
        FROM event_images
        WHERE event_id = ?
        ORDER BY display_order ASC, id ASC
    ");

    $stmt2->execute([$e['id']]);

    $imgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $e['images'] = [];

    foreach ($imgs as $img) {

        $e['images'][] = [
            'id' => $img['id'],
            'image' => $UPLOAD_DIR_URL . $img['image_path'],
            'comment' => $img['description'],
            'display_order' => $img['display_order']
        ];

    }
}

echo json_encode(
    $events,
    JSON_UNESCAPED_UNICODE
);