// account.js
const API_URL = "account_api.php";
const tbody = document.getElementById("accountTbody");

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

async function submitPassword(ev, userId) {
    ev.preventDefault();

    const form = ev.target;
    const newPassword = form.new_password.value;

    if (!confirm("パスワードを変更しますか？")) return false;

    const fd = new FormData();
    fd.append("action", "change_password");
    fd.append("user_id", userId);
    fd.append("new_password", newPassword);

    const res = await fetch(API_URL, {
        method: "POST",
        body: fd,
        credentials: "include",
    });

    if (res.status === 401) {
        location.href = "../login/login.php";
        return false;
    }
    if (res.status === 403) {
        alert("権限がありません");
        return false;
    }

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.ok) {
        alert("失敗: " + (data.error ?? "unknown"));
        return false;
    }

    alert("パスワード変更OK");
    form.reset();
    return false;
}

function render(data) {
    const users = data.users ?? [];

    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="4">ユーザーがいません</td></tr>`;
        return;
    }

    tbody.innerHTML = users
        .map((u) => {
            const id = Number(u.id);

            const name = u.can_change
                ? `<a href="account_detail.html?user_id=${encodeURIComponent(id)}">${escapeHtml(u.name)}</a>`
                : escapeHtml(u.name);

            const passCell = u.can_change_password
                ? `
          <form class="pass-form" onsubmit="return submitPassword(event, ${escapeHtml(id)})">
            <input type="password" name="new_password" placeholder="新パス（4文字以上）" required minlength="4">
            <button class="pass-btn" type="submit">変更</button>
          </form>
        `
                : "—";

            const deleteCell = u.can_delete
                ? `
          <form method="POST" action="account_delete.php"
                onsubmit="return confirm('このアカウントを削除しますか？');">
            <input type="hidden" name="user_id" value="${escapeHtml(id)}">
            <button class="delete-btn" type="submit">削除</button>
          </form>
        `
                : "—";

            return `
        <tr>
          <td data-label="ログインID">${escapeHtml(u.login_id)}</td>
          <td data-label="名前">${name}</td>
          <td data-label="パスワード変更">${passCell}</td>
          <td data-label="削除">${deleteCell}</td>
        </tr>
      `;
        })
        .join("");
}

async function load() {
    try {
        const res = await fetch(API_URL, { cache: "no-store", credentials: "include" });

        if (res.status === 401) {
            location.href = "../login/login.php";
            return;
        }
        if (res.status === 403) {
            tbody.innerHTML = `<tr><td colspan="4">このページを閲覧する権限がありません</td></tr>`;
            return;
        }
        if (!res.ok) throw new Error("fetch failed");

        const data = await res.json();
        render(data);
    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="4">読み込みに失敗しました</td></tr>`;
    }
}

load();
