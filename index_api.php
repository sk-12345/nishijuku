<?php
declare(strict_types=1);

require_once __DIR__ . '/code/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$sources = [
    'event' => [
        'table' => 'events',
        'image_table' => 'event_images',
        'foreign_key' => 'event_id',
        'image_dir' => 'events',
        'label' => 'イベント',
        'url' => 'code/event/event.html',
    ],
    'practice' => [
        'table' => 'practices',
        'image_table' => 'practice_images',
        'foreign_key' => 'practice_id',
        'image_dir' => 'practices',
        'label' => '練習',
        'url' => 'code/practice/practice.html',
    ],
    'game' => [
        'table' => 'games',
        'image_table' => 'game_images',
        'foreign_key' => 'game_id',
        'image_dir' => 'games',
        'label' => '試合',
        'url' => 'code/game/game.html',
    ],
];

$latest = [];

try {
    foreach ($sources as $type => $source) {
        $post = $pdo->query(
            "SELECT id, title, description, append_datetime
             FROM {$source['table']}
             ORDER BY append_datetime DESC, id DESC
             LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $latest[] = [
                'type' => $type,
                'label' => $source['label'],
                'url' => $source['url'],
                'empty' => true,
            ];
            continue;
        }

        $imageStatement = $pdo->prepare(
            "SELECT image_path
             FROM {$source['image_table']}
             WHERE {$source['foreign_key']} = ?
             ORDER BY display_order ASC, id ASC
             LIMIT 1"
        );
        $imageStatement->execute([(int)$post['id']]);
        $imagePath = $imageStatement->fetchColumn();

        $latest[] = [
            'type' => $type,
            'label' => $source['label'],
            'title' => (string)$post['title'],
            'description' => (string)$post['description'],
            'date' => (string)$post['append_datetime'],
            'image' => $imagePath ? 'img/' . $source['image_dir'] . '/' . basename((string)$imagePath) : null,
            'url' => $source['url'],
            'empty' => false,
        ];
    }

    echo json_encode(['items' => $latest], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => '最新情報を取得できませんでした。'], JSON_UNESCAPED_UNICODE);
}
