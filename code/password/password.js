const params = new URLSearchParams(location.search);
const msgEl = document.getElementById("msg");

function showMsg(text, isError) {
    if (!msgEl) return;
    msgEl.textContent = text;
    msgEl.style.display = "block";
    msgEl.className = isError ? "msg error" : "msg ok";
}

if (params.has("ok")) {
    showMsg("パスワードを変更しました。", false);
}

const err = params.get("err");
if (err) {
    // errコードをメッセージに変換
    const map = {
        "mismatch": "新しいパスワード（確認）が一致しません。",
        "current": "現在のパスワードが違います。",
        "short": "新しいパスワードは6文字以上にしてください。",
        "failed": "変更に失敗しました。",
    };
    showMsg(map[err] ?? "入力内容を確認してください。", true);
}
