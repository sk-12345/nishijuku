<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'login_required']);
    exit;
}

$role_id = (int)$_SESSION['user']['role_id'];
$user_id = (int)$_SESSION['user']['id'];
$user_name = $_SESSION['user']['fullname'] ?? $_SESSION['user']['name'] ?? '';

$UPLOAD_DIR_REAL = __DIR__ . '/../../img/events/';
$UPLOAD_DIR_URL  = '/nishijuku/img/events/';

if (!is_dir($UPLOAD_DIR_REAL)) {
    mkdir($UPLOAD_DIR_REAL, 0777, true);
}

$can_post   = in_array($role_id, [1, 2, 3, 5], true);
$can_delete = ($role_id !== 4);

function saveEventImage($file, $uploadDirReal) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allow, true)) {
        return null;
    }

    $filename = uniqid('event_', true) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $uploadDirReal . $filename)) {
        return null;
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {

        if (!$can_delete) {
            http_response_code(403);
            echo json_encode(['error' => 'no_delete_permission']);
            exit;
        }

        $id = (int)($_POST['delete_id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT image_path
            FROM event_images
            WHERE event_id = ?
        ");
        $stmt->execute([$id]);
        $imgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($imgs as $img) {
            $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

            if (is_file($file)) {
                unlink($file);
            }
        }

        $pdo->prepare("
            DELETE FROM event_images
            WHERE event_id = ?
        ")->execute([$id]);

        $pdo->prepare("
            DELETE FROM events
            WHERE id = ?
        ")->execute([$id]);

        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'add') {

        if (!$can_post) {
            http_response_code(403);
            echo json_encode(['error' => 'no_post_permission']);
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        $image_comments = $_POST['image_comments'] ?? [];
        $display_orders = $_POST['display_orders'] ?? [];

        if ($title === '' || $description === '') {
            http_response_code(400);
            echo json_encode(['error' => 'required']);
            exit;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO events
                    (title, description, create_by, create_at)
                VALUES
                    (?, ?, ?, NOW())
            ");

            $stmt->execute([
                $title,
                $description,
                $user_id
            ]);

            $event_id = $pdo->lastInsertId();

            if (isset($_FILES['images'])) {

                foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {

                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ];

                    $filename = saveEventImage($file, $UPLOAD_DIR_REAL);

                    if ($filename === null) {
                        continue;
                    }

                    $comment = $image_comments[$i] ?? '';
                    $order = (int)($display_orders[$i] ?? ($i + 1));

                    $stmt = $pdo->prepare("
                        INSERT INTO event_images
                            (
                                event_id,
                                description,
                                image_path,
                                display_order
                            )
                        VALUES
                            (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $event_id,
                        $comment,
                        $filename,
                        $order
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode(['ok' => true]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();

            http_response_code(500);
            echo json_encode([
                'error' => 'db_error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    if ($action === 'edit') {

        if (!$can_post) {
            http_response_code(403);
            echo json_encode(['error' => 'no_edit_permission']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id <= 0 || $title === '' || $description === '') {
            http_response_code(400);
            echo json_encode(['error' => 'required']);
            exit;
        }

        $existing_ids = $_POST['existing_image_ids'] ?? [];
        $existing_comments = $_POST['existing_image_comments'] ?? [];
        $delete_image_ids = $_POST['delete_image_ids'] ?? [];

        $new_comments = $_POST['new_image_comments'] ?? [];
        $new_orders = $_POST['new_display_orders'] ?? [];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                UPDATE events
                SET
                    title = ?,
                    description = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $title,
                $description,
                $id
            ]);

            foreach ($delete_image_ids as $delete_image_id) {
                $delete_image_id = (int)$delete_image_id;

                $stmt = $pdo->prepare("
                    SELECT image_path
                    FROM event_images
                    WHERE id = ?
                      AND event_id = ?
                ");

                $stmt->execute([
                    $delete_image_id,
                    $id
                ]);

                $img = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($img) {
                    $file = $UPLOAD_DIR_REAL . basename($img['image_path']);

                    if (is_file($file)) {
                        unlink($file);
                    }

                    $stmt = $pdo->prepare("
                        DELETE FROM event_images
                        WHERE id = ?
                          AND event_id = ?
                    ");

                    $stmt->execute([
                        $delete_image_id,
                        $id
                    ]);
                }
            }

            foreach ($existing_ids as $i => $image_id) {
                $image_id = (int)$image_id;
                $comment = $existing_comments[$i] ?? '';
                $order = $i + 1;

                $stmt = $pdo->prepare("
                    UPDATE event_images
                    SET
                        description = ?,
                        display_order = ?
                    WHERE id = ?
                      AND event_id = ?
                ");

                $stmt->execute([
                    $comment,
                    $order,
                    $image_id,
                    $id
                ]);
            }

            if (isset($_FILES['new_images'])) {

                foreach ($_FILES['new_images']['tmp_name'] as $i => $tmp) {

                    $file = [
                        'name' => $_FILES['new_images']['name'][$i],
                        'type' => $_FILES['new_images']['type'][$i],
                        'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
                        'error' => $_FILES['new_images']['error'][$i],
                        'size' => $_FILES['new_images']['size'][$i],
                    ];

                    $filename = saveEventImage($file, $UPLOAD_DIR_REAL);

                    if ($filename === null) {
                        continue;
                    }

                    $comment = $new_comments[$i] ?? '';
                    $order = (int)($new_orders[$i] ?? ($i + 1));

                    $stmt = $pdo->prepare("
                        INSERT INTO event_images
                            (
                                event_id,
                                description,
                                image_path,
                                display_order
                            )
                        VALUES
                            (?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $id,
                        $comment,
                        $filename,
                        $order
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode(['ok' => true]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();

            http_response_code(500);
            echo json_encode([
                'error' => 'db_error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(['error' => 'unknown_action']);
    exit;
}

$stmt = $pdo->query("
    SELECT
        id,
        title,
        description,
        create_by,
        DATE_FORMAT(create_at, '%Y/%m/%d %H:%i') AS create_at
    FROM events
    ORDER BY create_at DESC
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
            'image_path' => $img['image_path'],
            'image' => $UPLOAD_DIR_URL . $img['image_path'],
            'comment' => $img['description'],
            'display_order' => $img['display_order']
        ];
    }
}

echo json_encode([
    'events' => $events,
    'me' => [
        'role_id' => $role_id,
        'can_post' => $can_post,
        'can_delete' => $can_delete
    ]
], JSON_UNESCAPED_UNICODE);