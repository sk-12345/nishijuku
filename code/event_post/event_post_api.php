<?php
session_start();
require_once '../db.php';
require_once '../photo_update_helper.php';

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
$audit_user = (string)($_SESSION['user']['login_id'] ?? $user_id);


/* =========================
   アップロード設定
========================= */

$UPLOAD_DIR_REAL = __DIR__ . '/../../img/events/';
$UPLOAD_DIR_URL  = '/nishijuku/img/events/';   // ← 本番用

if (!is_dir($UPLOAD_DIR_REAL)) {
    mkdir($UPLOAD_DIR_REAL, 0777, true);
}


/* =========================
   権限
========================= */

$can_post   = in_array($role_id, [1,2,3,5], true);
$can_delete = ($role_id !== 4);


/* =========================
   POST処理
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? "";

    if ($action === "update") {
        if (!$can_post) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($id <= 0 || $title === '' || $description === '') { http_response_code(400); exit; }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                UPDATE events SET title=?, description=?, update_datetime=NOW(),
                    update_user_id=?, update_func_id='event_post', lock_timestamp=CURRENT_TIMESTAMP
                WHERE id=?
            ");
            $stmt->execute([$title, $description, $audit_user, $id]);
            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare("SELECT id FROM events WHERE id=?");
                $check->execute([$id]);
                if (!$check->fetchColumn()) throw new RuntimeException('event_not_found');
            }
            savePhotoManifest($pdo, 'event_images', 'event_id', $id, $UPLOAD_DIR_REAL, 'event', $audit_user, 'event_post');
            $pdo->commit();
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error'=>$e->getMessage()]);
        }
        exit;
    }


    /* =========================
       削除
    ========================= */

    if ($action === "delete") {

        if(!$can_delete){
            http_response_code(403);
            echo json_encode(['error'=>'no_delete_permission']);
            exit;
        }

        $id = (int)($_POST['delete_id'] ?? 0);


        // 画像取得
        $stmt = $pdo->prepare("
            SELECT image_path
            FROM event_images
            WHERE event_id=?
        ");

        $stmt->execute([$id]);

        $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // ファイル削除
        foreach ($imgs as $img) {

            $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

            if (is_file($file)) {
                unlink($file);
            }

        }


        // DB削除
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
            (title, description,
             append_datetime, append_user_id, append_func_id,
             update_datetime, update_user_id, update_func_id, lock_timestamp)
            VALUES(?,?,NOW(),?,'event_post',NOW(),?,'event_post',CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $title,
            $description,
            $audit_user,
            $audit_user
        ]);

        $event_id = $pdo->lastInsertId();

        savePhotoManifest($pdo, 'event_images', 'event_id', (int)$event_id, $UPLOAD_DIR_REAL, 'event', $audit_user, 'event_post');



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
                    (event_id, description, image_path, display_order,
                     append_datetime, append_user_id, append_func_id,
                     update_datetime, update_user_id, update_func_id, lock_timestamp)
                    VALUES(?,?,?,?,NOW(),?,'event_post',NOW(),?,'event_post',CURRENT_TIMESTAMP)
                ");

                $stmt->execute([
                    $event_id,
                    $comment,
                    $filename,
                    $i + 1,
                    $audit_user,
                    $audit_user
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
    SELECT events.*, append_datetime AS created_at
    FROM events
    ORDER BY append_datetime DESC
");

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($events as &$e) {

    $stmt2 = $pdo->prepare("
        SELECT id,image_path,description
        FROM event_images
        WHERE event_id=?
        ORDER BY display_order ASC, id ASC
    ");

    $stmt2->execute([$e['id']]);

    $imgs = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $e['images'] = [];

    foreach ($imgs as $img) {

        $e['images'][] = [
            'id'      => (int)$img['id'],
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
], JSON_UNESCAPED_UNICODE);
