<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../db.php';

ini_set('display_errors', '0');
error_reporting(E_ALL);

const ROLE_PHOTO   = 3;

if (!isset($_SESSION['user'])) {
  header("Location: /nishijuku/code/login/login.html");
  exit;
}

$roleId = (int)($_SESSION['user']['role_id'] ?? 0);
if ($roleId !== ROLE_PHOTO) {
  echo "写真ユーザーのみ表示できます。";
  exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>写真権限の譲渡</title>
  <style>
    body { font-family: "メイリオ", sans-serif; margin: 0; background: #f4f6fb; }
    .wrap { width: min(720px, 92vw); margin: 24px auto; background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 10px 25px rgba(0,0,0,.12); }
    h2 { margin: 0 0 12px; }
    label { display:block; margin: 10px 0 6px; font-weight: bold; }
    input { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #ddd; }
    button { margin-top: 14px; width:100%; padding: 12px; border: 0; border-radius: 12px; font-weight: bold; cursor:pointer; }
    .danger { background: #ff4d4f; color:#fff; }
    .msg { margin-top: 12px; padding: 10px; border-radius: 10px; background:#f1f3f5; }
    .note { font-size: 13px; color:#666; margin-top: 10px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h2>写真権限の譲渡</h2>

    <div class="note">
      ・譲渡すると、あなたは一般ユーザーになります。<br>
      ・譲渡先は「一般ユーザー」のみ指定できます。
    </div>

    <form id="f">
      <label>譲渡先ユーザーID</label>
      <input type="number" name="to_user_id" min="1" required>

      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
      <button class="danger" type="submit">譲渡する</button>
    </form>

    <div id="msg" class="msg" style="display:none;"></div>
  </div>

<script>
const f = document.getElementById('f');
const msg = document.getElementById('msg');

f.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!confirm('本当に写真権限を譲渡しますか？\n（あなたは一般ユーザーになります）')) return;

  msg.style.display = 'block';
  msg.textContent = '処理中...';

  const fd = new FormData(f);
  const res = await fetch('photo_role_transfer_api.php', { method: 'POST', body: fd });
  const data = await res.json().catch(() => null);

  if (!data) {
    msg.textContent = '通信エラー';
    return;
  }
  msg.textContent = data.ok ? data.message : (data.message || data.error);

  if (data.ok) {
    // 譲渡後はホームへ飛ばす等
    setTimeout(() => location.href = '/nishijuku/home/home.php', 800);
  }
});
</script>
</body>
</html>
