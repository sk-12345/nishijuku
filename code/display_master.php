<?php
declare(strict_types=1);

/**
 * 標準マスタを返す。
 */
function displayMasterRows(PDO $pdo, string $master): array
{
    $definitions = [
        'koumoku' => ['table' => 'koumoku', 'key' => 'koumoku_id'],
        'button' => ['table' => 'button', 'key' => 'button_id'],
        'code' => ['table' => 'code', 'key' => 'code_id', 'order' => 'sort_order, id'],
        'message' => ['table' => 'message', 'key' => 'message_id'],
    ];
    if (!isset($definitions[$master])) {
        throw new InvalidArgumentException('Unknown display master: ' . $master);
    }

    $definition = $definitions[$master];
    $sql = sprintf(
        'SELECT `%s` AS system_key, display_name, description FROM `%s` ORDER BY %s',
        $definition['key'],
        $definition['table'],
        $definition['order'] ?? 'id'
    );
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function standardDisplayData(PDO $pdo): array
{
    return [
        'koumoku' => displayMasterRows($pdo, 'koumoku'),
        'button' => displayMasterRows($pdo, 'button'),
        'code' => displayMasterRows($pdo, 'code'),
        'message' => displayMasterRows($pdo, 'message'),
    ];
}
