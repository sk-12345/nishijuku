session_start();

if (!isset($_SESSION['user'])) {
    exit('不正アクセス');
}

$role = $_SESSION['user']['role'];

// ✅ ADMIN は GENERAL / PHOTE のみ許可
if ($role === 'ADMIN') {
    if ($_POST['role_id'] == 1 || $_POST['role_id'] == 2) {
        exit('この権限は作成できません');
    }
}

// ✅ GENERAL / PHOTE は作成不可
if ($role === 'GENERAL' || $role === 'PHOTE') {
    exit('アカウント作成権限がありません');
}
