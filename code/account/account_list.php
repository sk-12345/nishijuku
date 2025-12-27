<?php
session_start();
require_once '../db.php';

// ✅ ログイン必須
if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

$myId   = (int)$_SESSION['user']['id'];
$myRole = (int)$_SESSION['user']['role_id']; // 1=SYSTEM, 2=ADMIN, 3=GENERAL, 4=PHOTO

// ✅ SYSTEM / ADMIN 以外は見れない
if (!in_array($myRole, [1, 2], true)) {
    exit('このページを閲覧する権限がありません');
}

// ✅ roles を取得（プルダウン用）
$rolesStmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY id");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ users + roles を結合して取得（表示用に role_name も取る）
$stmt = $pdo->query("
    SELECT
        u.id,
        u.login_id,
        u.name,
        u.role_id,
        r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ 自分の権限で「変更先の候補」を絞る
function getSelectableRoleIds(int $myRole): array {
    // SYSTEM：事故防止で「1(SYSTEM)は変更先に出さない」例
    if ($myRole === 1) return [2, 3, 4];

    // ADMIN：GENERAL/PHOTO のみに変更できる例
    if ($myRole === 2) return [3, 4];

    return [];
}

// ✅ 「この相手の権限を変更できるか」判定
function canChangeRole(int $myRole, int $myId, array $targetUser): bool {
    $targetId   = (int)$targetUser['id'];
    $targetRole = (int)$targetUser['role_id'];

    // 自分自身は変更不可
    if ($myId === $targetId) return false;

    // SYSTEM：SYSTEM(1)の人は触らない（事故防止） / それ以外は変更OK
    if ($myRole === 1) return $targetRole !== 1;

    // ADMIN：GENERAL(3) / PHOTO(4) の人だけ変更OK
    if ($myRole === 2) return in_array($targetRole, [3, 4], true);

    return false;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>アカウント管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="account.css">
</head>
<body>

<div class="account-wrapper">

    <h2>アカウント管理</h2>

    <table class="account-table">
        <tr>
            <th>ID</th>
            <th>ログインID</th>
            <th>名前</th>
            <th>権限</th>
            <th>権限変更</th>
            <th>削除</th>
        </tr>

        <?php foreach ($users as $user): ?>
        <?php
            $canChange = canChangeRole($myRole, $myId, $user);
            $selectableIds = getSelectableRoleIds($myRole);
        ?>
        <tr>
            <td><?= (int)$user['id'] ?></td>
            <td><?= htmlspecialchars($user['login_id']) ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['role_name']) ?></td>

            <td>
                <?php if ($canChange): ?>
                    <form method="POST" action="account_role_update.php"
                          onsubmit="return confirm('権限を変更しますか？');">
                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">

                        <select name="role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <?php if (in_array((int)$r['id'], $selectableIds, true)): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= ((int)$user['role_id'] === (int)$r['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['role_name']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>

                        <button class="role-btn">更新</button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>

            <td>
                <?php
                // ✅ 削除できるか（例：SYSTEMはSYSTEM以外、ADMINはGENERAL/PHOTOのみ）
                $canDelete = false;
                $targetId   = (int)$user['id'];
                $targetRole = (int)$user['role_id'];

                if ($myId !== $targetId) {
                    if ($myRole === 1 && $targetRole !== 1) $canDelete = true;
                    if ($myRole === 2 && in_array($targetRole, [3, 4], true)) $canDelete = true;
                }
                ?>

                <?php if ($canDelete): ?>
                    <form method="POST" action="account_delete.php"
                          onsubmit="return confirm('このアカウントを削除しますか？');">
                        <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                        <button class="delete-btn">削除</button>
                    </form>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>

    </table>

    <p class="back"><a href="../home/home.php">← ホームに戻る</a></p>

</div>

</body>
</html>
