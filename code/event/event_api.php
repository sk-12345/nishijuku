<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/events/';

$stmt=$pdo->query("
SELECT *
FROM events
ORDER BY created_at DESC
");

$events=$stmt->fetchAll(PDO::FETCH_ASSOC);


foreach($events as &$e){

    $stmt2=$pdo->prepare("
        SELECT image_path
        FROM event_images
        WHERE event_id=?
    ");

    $stmt2->execute([$e['id']]);

    $imgs=$stmt2->fetchAll(PDO::FETCH_ASSOC);

    $e['images']=[];

    foreach($imgs as $img){

        $e['images'][]=
            $UPLOAD_DIR_URL.$img['image_path'];

    }

}


echo json_encode(
    $events,
    JSON_UNESCAPED_UNICODE
);