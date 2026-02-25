<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

// =========================
// ログイン必須
// =========================
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit();
}

$role_id = (int)($_SESSION['user']['role_id'] ?? 0);
$user_id = (int)($_SESSION['user']['id'] ?? 0);

// =========================
// パス定義
// =========================
$UPLOAD_DIR_REAL = __DIR__ . '/../../img/practices/';
$UPLOAD_DIR_URL  = '/nishijuku/img/practices/';

if (!is_dir($UPLOAD_DIR_REAL)) {
    @mkdir($UPLOAD_DIR_REAL, 0777, true);
}

// 投稿/削除可否
$can_post   = in_array($role_id, [1,2,3,5], true);
$can_delete = ($role_id !== 4);

// =========================
// POST処理
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // =====================
    // 削除
    // =====================
    if ($action === 'delete') {

        if (!$can_delete) {
            http_response_code(403);
            echo json_encode(['error'=>'no_delete_permission']);
            exit;
        }

        $delete_id = (int)($_POST['delete_id'] ?? 0);

        // 画像取得
        $stmt = $pdo->prepare("
            SELECT image_path
            FROM practice_images
            WHERE practice_id=?
        ");
        $stmt->execute([$delete_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($images as $img){

            $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

            if(is_file($file)){
                @unlink($file);
            }
        }

        // DB削除
        $pdo->prepare("DELETE FROM practice_images WHERE practice_id=?")
            ->execute([$delete_id]);

        $pdo->prepare("DELETE FROM practices WHERE id=?")
            ->execute([$delete_id]);

        echo json_encode(['ok'=>true]);
        exit;
    }

    // =====================
    // 投稿
    // =====================
    if ($action === 'add') {

        if (!$can_post) {
            http_response_code(403);
            echo json_encode(['error'=>'no_post_permission']);
            exit;
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title=='' || $description==''){
            http_response_code(400);
            echo json_encode(['error'=>'title_required']);
            exit;
        }

        if (!isset($_FILES['images'])){
            http_response_code(400);
            echo json_encode(['error'=>'images_required']);
            exit;
        }

        // 投稿登録
        $stmt=$pdo->prepare("
            INSERT INTO practices
            (title,description,created_by,created_at)
            VALUES(?,?,?,NOW())
        ");

        $stmt->execute([
            $title,
            $description,
            $user_id
        ]);

        $practice_id=$pdo->lastInsertId();

        // 画像保存
        foreach($_FILES['images']['tmp_name'] as $i=>$tmp){

            if($_FILES['images']['error'][$i]!==UPLOAD_ERR_OK){
                continue;
            }

            $ext=strtolower(
                pathinfo($_FILES['images']['name'][$i],PATHINFO_EXTENSION)
            );

            $allowed=['jpg','jpeg','png','gif','webp'];

            if(!in_array($ext,$allowed,true)){
                continue;
            }

            $filename=uniqid('practice_',true).'.'.$ext;

            move_uploaded_file(
                $tmp,
                $UPLOAD_DIR_REAL.$filename
            );

            // DB登録
            $pdo->prepare("
                INSERT INTO practice_images
                (practice_id,image_path)
                VALUES(?,?)
            ")->execute([
                $practice_id,
                $filename
            ]);

        }

        echo json_encode(['ok'=>true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error'=>'unknown_action']);
    exit;
}


// =========================
// GET一覧取得
// =========================

$stmt=$pdo->query("
SELECT *
FROM practices
ORDER BY created_at DESC
");

$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);


// 画像結合
foreach($rows as &$r){

    $stmt2=$pdo->prepare("
        SELECT image_path
        FROM practice_images
        WHERE practice_id=?
    ");

    $stmt2->execute([$r['id']]);

    $imgs=$stmt2->fetchAll(PDO::FETCH_ASSOC);

    $r['images']=[];

    foreach($imgs as $img){

        $r['images'][]=
            $UPLOAD_DIR_URL.$img['image_path'];
    }

}


echo json_encode([
    'me'=>[
        'role_id'=>$role_id,
        'can_post'=>$can_post,
        'can_delete'=>$can_delete
    ],
    'practices'=>$rows
],JSON_UNESCAPED_UNICODE);