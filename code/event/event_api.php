<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL='/nishijuku/img/events/';

$stmt=$pdo->query("
SELECT events.*, append_datetime AS created_at
FROM events
ORDER BY append_datetime DESC
");

$events=$stmt->fetchAll(PDO::FETCH_ASSOC);


foreach($events as &$e){

$stmt2=$pdo->prepare("
SELECT image_path,description
FROM event_images
WHERE event_id=?
ORDER BY display_order ASC, id ASC
");

$stmt2->execute([$e['id']]);

$imgs=$stmt2->fetchAll(PDO::FETCH_ASSOC);

$e['images']=[];

foreach($imgs as $img){

$e['images'][]=[
"image"=>$UPLOAD_DIR_URL.$img['image_path'],
"comment"=>$img['description']
];

}

}

echo json_encode(
$events,
JSON_UNESCAPED_UNICODE
);
