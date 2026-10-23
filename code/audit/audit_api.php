<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

requirePermission($pdo, 'update_confirmation_flg');

try {
    $sql = <<<'SQL'
        SELECT target_type, target_name, updated_by, updated_at
        FROM (
            SELECT 'アカウント' AS target_type,
                   CONCAT(name, '（', login_id, '）') AS target_name,
                   update_user_id AS updated_by,
                   update_datetime AS updated_at
            FROM users
            UNION ALL
            SELECT '練習風景', title, update_user_id, update_datetime FROM practices
            UNION ALL
            SELECT '試合', title, update_user_id, update_datetime FROM games
            UNION ALL
            SELECT 'イベント', title, update_user_id, update_datetime FROM events
        ) AS audit_rows
        ORDER BY updated_at DESC, target_type ASC, target_name ASC
        SQL;

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error'], JSON_UNESCAPED_UNICODE);
}
