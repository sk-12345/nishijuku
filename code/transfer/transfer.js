const form = document.getElementById("transferForm");
const msg = document.getElementById("msg");
const csrfEl = document.getElementById("csrf_token");
const submitBtn = document.getElementById("submitBtn");

function showMsg(text) {
  msg.textContent = text;
  msg.classList.add("show");
}

async function loadCsrf() {
  try {
    const res = await fetch("csrf_token_api.php", { cache: "no-store" });
    const data = await res.json();

    if (!data || !data.ok) {
      showMsg("CSRFトークン取得に失敗しました。\nログイン状態か確認してください。");
      submitBtn.disabled = true;
      return;
    }
    csrfEl.value = data.csrf_token;
  } catch (e) {
    showMsg("CSRFトークン取得で通信エラーが発生しました。");
    submitBtn.disabled = true;
  }
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const toUserId = Number(form.to_user_id.value || 0);
  if (!toUserId) {
    showMsg("譲渡先ユーザーIDを入力してください。");
    return;
  }

  if (!csrfEl.value) {
    showMsg("CSRFトークンがありません。ページを更新してください。");
    return;
  }

  const ok = confirm("本当に写真権限を譲渡しますか？\n（あなたは一般ユーザーになります）");
  if (!ok) return;

  submitBtn.disabled = true;
  showMsg("処理中...");

  try {
    const fd = new FormData(form);
    const res = await fetch("photo_role_transfer_api.php", {
      method: "POST",
      body: fd
    });

    const data = await res.json().catch(() => null);

    if (!data) {
      showMsg("サーバー応答が不正です。");
      submitBtn.disabled = false;
      return;
    }

    if (data.ok) {
      showMsg(data.message || "譲渡しました。");
      setTimeout(() => {
        location.href = "/nishijuku/home/home.php";
      }, 900);
    } else {
      showMsg(data.message || data.error || "失敗しました。");
      submitBtn.disabled = false;

      // 失敗したらトークン更新（古いトークン対策）
      loadCsrf();
    }
  } catch (e) {
    showMsg("通信エラーが発生しました。");
    submitBtn.disabled = false;
  }
});

loadCsrf();
