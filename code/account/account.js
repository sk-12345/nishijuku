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

function buildRoleOptions(roles, selectableIds, currentRoleId) {
    return roles
        .filter(r => selectableIds.includes(Number(r.id)))
        .map(r => {
            const selected = Number(r.id) === Number(currentRoleId) ? "selected" : "";
            return `<option value="${escapeHtml(r.id)}" ${selected}>${escapeHtml(r.role_name)}</option>`;
        })
        .join("");
}

function render(data) {
    const roles = data.roles ?? [];
    const users = data.users ?? [];
    const selectableIds = data.me?.selectable_role_ids ?? [];

    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="6">ユーザーがいません</td></tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => {
        const id = Number(u.id);
        const canChange = !!u.can_change;
        const canDelete = !!u.can_delete;

        const changeCell = canChange
            ? `
        <form method="POST" action="account_role_update.php" onsubmit="return confirm('権限を変更しますか？');">
          <input type="hidden" name="user_id" value="${escapeHtml(id)}">
          <select name="role_id" required>
            ${buildRoleOptions(roles, selectableIds, u.role_id)}
          </select>
          <button class="role-btn" type="submit">更新</button>
        </form>
      `
            : `—`;

        const deleteCell = canDelete
            ? `
        <form method="POST" action="account_delete.php" onsubmit="return confirm('このアカウントを削除しますか？');">
          <input type="hidden" name="user_id" value="${escapeHtml(id)}">
          <button class="delete-btn" type="submit">削除</button>
        </form>
      `
            : `—`;

        return `
      <tr>
        <td>${escapeHtml(u.id)}</td>
        <td>${escapeHtml(u.login_id)}</td>
        <td>${escapeHtml(u.name)}</td>
        <td>${escapeHtml(u.role_name)}</td>
        <td>${changeCell}</td>
        <td>${deleteCell}</td>
      </tr>
    `;
    }).join("");
}

async function load() {
    try {
        const res = await fetch(API_URL, { cache: "no-store" });

        if (res.status === 401) {
            location.href = "../login/login.php";
            return;
        }
        if (res.status === 403) {
            tbody.innerHTML = `<tr><td colspan="6">このページを閲覧する権限がありません</td></tr>`;
            return;
        }
        if (!res.ok) throw new Error("fetch failed");

        const data = await res.json();
        render(data);
    } catch (e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="6">読み込みに失敗しました</td></tr>`;
    }
}

load();
