<?php
declare(strict_types=1);

function savePhotoManifest(
    PDO $pdo,
    string $table,
    string $parentColumn,
    int $parentId,
    string $uploadDir,
    string $filePrefix,
    string $auditUser,
    string $funcId
): void {
    $manifest = json_decode((string)($_POST['photo_manifest'] ?? '[]'), true);
    if (!is_array($manifest)) {
        throw new RuntimeException('invalid_photo_manifest');
    }

    $select = $pdo->prepare("SELECT id, image_path FROM {$table} WHERE {$parentColumn} = ?");
    $select->execute([$parentId]);
    $existing = [];
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $existing[(int)$row['id']] = (string)$row['image_path'];
    }

    $keptIds = [];
    $newFiles = $_FILES['new_images'] ?? null;

    foreach ($manifest as $order => $photo) {
        $comment = (string)($photo['comment'] ?? '');
        $displayOrder = $order + 1;

        if (($photo['kind'] ?? '') === 'existing') {
            $imageId = (int)($photo['id'] ?? 0);
            if (!isset($existing[$imageId])) throw new RuntimeException('invalid_existing_photo');
            $stmt = $pdo->prepare("
                UPDATE {$table}
                SET description = ?, display_order = ?, update_datetime = NOW(),
                    update_user_id = ?, update_func_id = ?, lock_timestamp = CURRENT_TIMESTAMP
                WHERE id = ? AND {$parentColumn} = ?
            ");
            $stmt->execute([$comment, $displayOrder, $auditUser, $funcId, $imageId, $parentId]);
            $keptIds[$imageId] = true;
            continue;
        }

        if (($photo['kind'] ?? '') !== 'new') throw new RuntimeException('invalid_photo_kind');
        $fileIndex = (int)($photo['fileIndex'] ?? -1);
        if (!$newFiles || ($newFiles['error'][$fileIndex] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('photo_upload_failed');
        }

        $original = (string)$newFiles['name'][$fileIndex];
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException('invalid_photo_extension');
        }
        $filename = uniqid($filePrefix . '_', true) . '.' . $extension;
        if (!move_uploaded_file($newFiles['tmp_name'][$fileIndex], $uploadDir . $filename)) {
            throw new RuntimeException('photo_save_failed');
        }

        $stmt = $pdo->prepare("
            INSERT INTO {$table} (
                {$parentColumn}, description, image_path, display_order,
                append_datetime, append_user_id, append_func_id,
                update_datetime, update_user_id, update_func_id, lock_timestamp
            ) VALUES (?, ?, ?, ?, NOW(), ?, ?, NOW(), ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$parentId, $comment, $filename, $displayOrder, $auditUser, $funcId, $auditUser, $funcId]);
        $keptIds[(int)$pdo->lastInsertId()] = true;
    }

    foreach ($existing as $imageId => $path) {
        if (isset($keptIds[$imageId])) continue;
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ? AND {$parentColumn} = ?");
        $stmt->execute([$imageId, $parentId]);
        $file = $uploadDir . basename($path);
        if (is_file($file)) unlink($file);
    }
}
