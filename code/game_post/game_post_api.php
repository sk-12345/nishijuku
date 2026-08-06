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

$UPLOAD_DIR_REAL = __DIR__ . '/../../img/games/';
$UPLOAD_DIR_URL  = '/nishijuku/img/games/';

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

    if ($action === "update") {
        if (!$can_post) { http_response_code(403); exit; }
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($id <= 0 || $title === '' || $description === '') { http_response_code(400); exit; }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE games SET title=?, description=?, update_datetime=NOW(), update_user_id=?, update_func_id='game_post', lock_timestamp=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$title, $description, $audit_user, $id]);
            $check = $pdo->prepare("SELECT id FROM games WHERE id=?");
            $check->execute([$id]);
            if (!$check->fetchColumn()) throw new RuntimeException('game_not_found');
            savePhotoManifest($pdo, 'game_images', 'game_id', $id, $UPLOAD_DIR_REAL, 'game', $audit_user, 'game_post');
            $pdo->commit();
            echo json_encode(['ok'=>true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500); echo json_encode(['error'=>$e->getMessage()]);
        }
        exit;
    }


    /* =========================
       削除
    ========================= */

    if ($action === "delete") {

        $id = (int)$_POST['delete_id'];

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM game_images
            WHERE game_id=?
        ");

        $stmt->execute([$id]);

        $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($imgs as $img) {

            $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

            if (is_file($file)) unlink($file);

        }

        $pdo->prepare("
            DELETE FROM game_images
            WHERE game_id=?
        ")->execute([$id]);

        $pdo->prepare("
            DELETE FROM games
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
            INSERT INTO games
            (title, description,
             append_datetime, append_user_id, append_func_id,
             update_datetime, update_user_id, update_func_id, lock_timestamp)
            VALUES(?,?,NOW(),?,'game_post',NOW(),?,'game_post',CURRENT_TIMESTAMP)
        ");

        $stmt->execute([
            $title,
            $description,
            $audit_user,
            $audit_user
        ]);

        $game_id = $pdo->lastInsertId();

        savePhotoManifest($pdo, 'game_images', 'game_id', (int)$game_id, $UPLOAD_DIR_REAL, 'game', $audit_user, 'game_post');



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

                $filename = uniqid('game_',true) . '.' . $ext;

                move_uploaded_file(
                    $tmp,
                    $UPLOAD_DIR_REAL . $filename
                );

                $comment = $image_comments[$i] ?? "";

                $stmt = $pdo->prepare("
                    INSERT INTO game_images
                    (game_id, description, image_path, display_order,
                     append_datetime, append_user_id, append_func_id,
                     update_datetime, update_user_id, update_func_id, lock_timestamp)
                    VALUES(?,?,?,?,NOW(),?,'game_post',NOW(),?,'game_post',CURRENT_TIMESTAMP)
                ");

                $stmt->execute([
                    $game_id,
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
    SELECT games.*, append_datetime AS created_at
    FROM games
    ORDER BY append_datetime DESC
");

$games = $stmt->fetchAll(PDO::FETCH_ASSOC);



foreach ($games as &$e) {

    $stmt2 = $pdo->prepare("
        SELECT id,image_path,description
        FROM game_images
        WHERE game_id=?
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
    'games'=>$games,
    'me'=>[
        'role_id'=>$role_id,
        'can_post'=>$can_post,
        'can_delete'=>$can_delete
    ]
]);
