const API_URL = "register_api.php";
const roleSelect = document.getElementById("roleSelect");
const msgEl = document.getElementById("msg");

function showMsg(text, isError) {
    if (!msgEl) return;
    msgEl.textContent = text;
    msgEl.style.display = "block";
    msgEl.className = isError ? "msg error" : "msg ok";
}

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

async function loadRoles() {
    const res = await fetch(API_URL, { cache: "no-store" });

    if (res.status === 401) {
        location.href = "../login/login.php";
        return;
    }
    if (res.status === 403) {
        showMsg("このページにアクセスする権限がありません", true);
        roleSelect.innerHTML = `<option value="">権限なし</option>`;
        roleSelect.disabled = true;
        return;
    }
    if (!res.ok) {
        showMsg("読み込みに失敗しました", true);
        roleSelect.innerHTML = `<option value="">エラー</option>`;
        return;
    }

    const data = await res.json();
    const roles = data.selectable_roles ?? [];

    if (!roles.length) {
        roleSelect.innerHTML = `<option value="">選択肢がありません</option>`;
        roleSelect.disabled = true;
        return;
    }

    roleSelect.innerHTML = roles
        .map(r => `<option value="${escapeHtml(r.id)}">${escapeHtml(r.name)}</option>`)
        .join("");
}

loadRoles();
