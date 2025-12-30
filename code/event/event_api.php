<?php

require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');


$UPLOAD_DIR_URL = '/nishijuku/img/uploads/';

$stmt = $pdo->query("SELECT * FROM events ORDER BY created_at DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DBには「ファイル名のみ」が入っている前提でURL化
foreach ($events as &$e) {
    $e['image_url'] = $UPLOAD_DIR_URL . ($e['image_path'] ?? '');
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
