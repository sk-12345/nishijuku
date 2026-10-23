const API_URL = "register_api.php";
const msgEl = document.getElementById("msg");
const permissionFieldset = document.getElementById("permissionFieldset");
const permissionList = document.getElementById("permissionList");
function showMsg(text) { if (!msgEl) return; msgEl.textContent = text; msgEl.style.display = "block"; msgEl.className = "msg error"; }
async function loadPermissions() {
    const res = await fetch(API_URL, { cache: "no-store", credentials: "include" });
    if (res.status === 401) { location.href = "../login/login.php"; return; }
    if (res.status === 403) { showMsg("このページにアクセスする権限がありません"); return; }
    if (!res.ok) { showMsg("権限情報の読み込みに失敗しました"); return; }
    const data = await res.json();
    if (!data.permission_labels) {
        showMsg("APIから権限の表示名を取得できませんでした");
        return;
    }
    permissionList.replaceChildren();
    for (const [flag, logicalName] of Object.entries(data.permission_labels)) {
        const label = document.createElement("label");
        const input = document.createElement("input");
        const span = document.createElement("span");

        input.type = "checkbox";
        input.name = flag;
        input.value = "1";
        input.disabled = flag === "system_flg" && data.me?.is_system !== true;
        span.textContent = logicalName || flag;

        label.append(input, span);
        permissionList.append(label);
    }
    const createButton = permissionFieldset.closest("form")?.querySelector('button[type="submit"]');
    const createName = (data.display_master?.button ?? []).find(row => row.system_key === "register");
    if (createButton && createName) createButton.textContent = createName.display_name;
    permissionFieldset.disabled = false;
}
loadPermissions().catch(() => showMsg("権限情報の読み込みに失敗しました"));
