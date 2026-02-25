<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');


if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$role_id=(int)$_SESSION['user']['role_id'];
$user_id=(int)$_SESSION['user']['id'];

$UPLOAD_DIR_REAL=__DIR__.'/../../img/events/';
$UPLOAD_DIR_URL='/nishijuku/img/events/';

if(!is_dir($UPLOAD_DIR_REAL)){
 mkdir($UPLOAD_DIR_REAL,0777,true);
}


$can_post=in_array($role_id,[1,2,3,5]);
$can_delete=($role_id!==4);



if($_SERVER['REQUEST_METHOD']==='POST'){

$action=$_POST['action']??"";


/* 削除 */
if($action==="delete"){

$id=(int)$_POST['delete_id'];

$stmt=$pdo->prepare("
SELECT image_path
FROM event_images
WHERE event_id=?
");

$stmt->execute([$id]);

$imgs=$stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($imgs as $img){

$file=$UPLOAD_DIR_REAL.basename($img['image_path']);

if(is_file($file)) unlink($file);

}

$pdo->prepare("
DELETE FROM event_images
WHERE event_id=?
")->execute([$id]);

$pdo->prepare("
DELETE FROM events
WHERE id=?
")->execute([$id]);

echo json_encode(['ok'=>true]);
exit;
}



/* 投稿 */
if($action==="add"){

$title=$_POST['title'];
$description=$_POST['description'];


$stmt=$pdo->prepare("
INSERT INTO events
(title,description,created_by,created_at)
VALUES(?,?,?,NOW())
");

$stmt->execute([
$title,
$description,
$user_id
]);

$event_id=$pdo->lastInsertId();



foreach($_FILES['images']['tmp_name'] as $i=>$tmp){

if($_FILES['images']['error'][$i]!==0)continue;

$ext=strtolower(
pathinfo(
$_FILES['images']['name'][$i],
PATHINFO_EXTENSION
)
);

$filename=
uniqid('event_',true)
.'.'.$ext;

move_uploaded_file(
$tmp,
$UPLOAD_DIR_REAL.$filename
);


$pdo->prepare("
INSERT INTO event_images
(event_id,image_path)
VALUES(?,?)
")->execute([
$event_id,
$filename
]);

}

echo json_encode(['ok'=>true]);
exit;
}

}



/* GET */

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


echo json_encode([
'events'=>$events,
'me'=>[
'role_id'=>$role_id,
'can_post'=>$can_post,
'can_delete'=>$can_delete
]
]);