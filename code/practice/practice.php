<?php
require_once '../db.php';

header('Content-Type: application/json; charset=UTF-8');

$UPLOAD_DIR_URL = '/nishijuku/img/practices/';

$stmt = $pdo->query("SELECT * FROM practices ORDER BY created_at DESC");
$practices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DBには「ファイル名のみ」が入っている前提でURL化
foreach ($practices as &$e) {
    $e['image_url'] = $UPLOAD_DIR_URL . ($e['image_path'] ?? '');
}

echo json_encode($practices, JSON_UNESCAPED_UNICODE);
