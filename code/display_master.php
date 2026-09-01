<?php
declare(strict_types=1);

/**
 * 標準マスタを表示順どおりに返す。システム用キーの重複は許可する。
 */
function displayMasterRows(PDO $pdo, string $master): array
{
    $definitions = [
        'koumoku' => ['table' => 'koumoku', 'key' => 'koumoku_key'],
        'button' => ['table' => 'button', 'key' => 'button_key'],
        'code' => ['table' => 'code', 'key' => 'code_key'],
        'message' => ['table' => 'message', 'key' => 'message_key'],
    ];
    if (!isset($definitions[$master])) {
        throw new InvalidArgumentException('Unknown display master: ' . $master);
    }

    $definition = $definitions[$master];
    $sql = sprintf(
        'SELECT `%s` AS system_key, display_name, description, sort_order FROM `%s` WHERE delete_flg = 0 ORDER BY `%s`, sort_order, `%s_id`',
        $definition['key'],
        $definition['table'],
        $definition['key'],
        $definition['table']
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
