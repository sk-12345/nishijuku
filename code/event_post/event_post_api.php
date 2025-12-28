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

// ✅ role_id で管理（数字）
$role_id = (int)($_SESSION['user']['role_id'] ?? 0);
$user_id = (int)($_SESSION['user']['id'] ?? 0);

// =========================
// パス定義
// =========================
$UPLOAD_DIR_REAL = __DIR__ . '/../../img/uploads/';
$UPLOAD_DIR_URL  = '/nishijuku/img/uploads/';

if (!is_dir($UPLOAD_DIR_REAL)) {
    mkdir($UPLOAD_DIR_REAL, 0777, true);
}

// 投稿/削除可否（フロント表示用）
$can_post   = in_array($role_id, [1, 2, 3], true);
$can_delete = ($role_id !== 4);

// =========================
// POST：追加 or 削除
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // ---- 削除 ----
    if ($action === 'delete') {

        if ($role_id === 4) {
            http_response_code(403);
            echo json_encode(['error' => 'no_delete_permission'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $delete_id = (int)($_POST['delete_id'] ?? 0);
        if ($delete_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_delete_id'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 画像ファイル取得
        $stmt = $pdo->prepare("SELECT image_path FROM events WHERE id = ?");
        $stmt->execute([$delete_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $filename = basename($event['image_path'] ?? '');
            $realPath = $UPLOAD_DIR_REAL . $filename;

            if ($filename !== '' && file_exists($realPath)) {
                @unlink($realPath);
            }

            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$delete_id]);
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- 追加 ----
    if ($action === 'add') {

        if (!$can_post) {
            http_response_code(403);
            echo json_encode(['error' => 'no_post_permission'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $description === '') {
            http_response_code(400);
            echo json_encode(['error' => 'title_description_required'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'image_required'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_image_ext'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $filename = uniqid('event_', true) . '.' . $ext;
        $realPath = $UPLOAD_DIR_REAL . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $realPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'upload_failed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, image_path, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $filename, $user_id]);

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'unknown_action'], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================
// GET：一覧取得
// =========================
$stmt = $pdo->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 画像URL付与
foreach ($events as &$e) {
    $e['image_url'] = $UPLOAD_DIR_URL . ($e['image_path'] ?? '');
}

echo json_encode([
    'me' => [
        'role_id' => $role_id,
        'can_post' => $can_post,
        'can_delete' => $can_delete,
    ],
    'events' => $events
], JSON_UNESCAPED_UNICODE);
