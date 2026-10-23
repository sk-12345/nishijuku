<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
require_once __DIR__ . '/../display_master.php';
header('Content-Type: application/json; charset=UTF-8');
$actor = requirePermission($pdo, 'create_account_flg');
$flgs = $pdo->prepare("SELECT COLUMN_NAME AS physical_name, COLUMN_COMMENT AS logical_name
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'roles'
      AND COLUMN_NAME LIKE '%flg%'
      AND COLUMN_NAME <> 'system_flg'
    ORDER BY ORDINAL_POSITION");
$flgs->execute();
$permissionLabels = [];
foreach ($flgs->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permissionLabels[$row['physical_name']] = $row['logical_name'] !== ''
        ? $row['logical_name']
        : $row['physical_name'];
}
echo json_encode([
    'me' => ['role_id' => (int)$actor['role_id'], 'is_system' => isSystemRole($actor)],
    'display_master' => standardDisplayData($pdo),
    'permission_labels' => (object) $permissionLabels,
], JSON_UNESCAPED_UNICODE);
