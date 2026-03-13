<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');


/* =========================
   ログインチェック
========================= */

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$role_id = (int)$_SESSION['user']['role_id'];
$user_id = (int)$_SESSION['user']['id'];


/* =========================
   アップロード設定
========================= */

$UPLOAD_DIR_REAL = __DIR__ . '/../../img/events/';
$UPLOAD_DIR_URL  = '/nishijuku/img/events/';

if (!is_dir($UPLOAD_DIR_REAL)) {
    mkdir($UPLOAD_DIR_REAL, 0777, true);
}


/* =========================
   権限
========================= */

$can_post   = in_array($role_id, [1,2,3,5]);
$can_delete = ($role_id !== 4);


/* =========================
   POST処理
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? "";


    /* =========================
       削除
    ========================= */

    if ($action === "delete") {

        $id = (int)$_POST['delete_id'];

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM event_images
            WHERE event_id=?
        ");

        $stmt->execute([$id]);

        $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($imgs as $img) {

            $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

            if (is_file($file)) unlink($file);

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



    /* =========================
       投稿
    ========================= */

    if ($action === "add") {

        if(!$can_post){
            http_response_code(403);
            exit;
        }

        $title       = $_POST['title'] ?? "";
        $description = $_POST['description'] ?? "";

        $image_comments = $_POST['image_comments'] ?? [];


        /* イベント作成 */

        $stmt = $pdo->prepare("
            INSERT INTO events
            (title,description,created_by,created_at)
            VALUES(?,?,?,NOW())
        ");

        $stmt->execute([
            $title,
            $description,
            $user_id
        ]);

        $event_id = $pdo->lastInsertId();



        /* 画像保存 */

        if(isset($_FILES['images'])){

            foreach($_FILES['images']['tmp_name'] as $i=>$tmp){

                if($_FILES['images']['error'][$i] !== 0) continue;

                $ext = strtolower(
                    pathinfo(
                        $_FILES['images']['name'][$i],
                        PATHINFO_EXTENSION
                    )
                );

                $filename = uniqid('event_',true) . '.' . $ext;

                move_uploaded_file(
                    $tmp,
                    $UPLOAD_DIR_REAL . $filename
                );

                $comment = $image_comments[$i] ?? "";

                $stmt = $pdo->prepare("
                    INSERT INTO event_images
                    (event_id,description,image_path)
                    VALUES(?,?,?)
                ");

                $stmt->execute([
                    $event_id,
                    $comment,
                    $filename
                ]);

            }

        }

        echo json_encode(['ok'=>true]);
        exit;

    }

}



/* =========================
   GET（イベント取得）
========================= */

$stmt = $pdo->query("
    SELECT *
    FROM events
    ORDER BY created_at DESC
");

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);



foreach ($events as &$e) {

    $stmt2 = $pdo->prepare("
        SELECT image_path,description
        FROM event_images
        WHERE event_id=?
    ");

    $stmt2->execute([$e['id']]);

    $imgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $e['images'] = [];

    foreach ($imgs as $img) {

        $e['images'][] = [
            'image'   => $UPLOAD_DIR_URL . $img['image_path'],
            'comment' => $img['description']
        ];

    }

}



/* =========================
   JSON返却
========================= */

echo json_encode([
    'events'=>$events,
    'me'=>[
        'role_id'=>$role_id,
        'can_post'=>$can_post,
        'can_delete'=>$can_delete
    ]
]);