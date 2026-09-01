const API_URL = "register_api.php";
const msgEl = document.getElementById("msg");
const permissionFieldset = document.getElementById("permissionFieldset");
const systemFlag = document.getElementById("systemFlag");
function showMsg(text) { if (!msgEl) return; msgEl.textContent = text; msgEl.style.display = "block"; msgEl.className = "msg error"; }
async function loadPermissions() {
    const res = await fetch(API_URL, { cache: "no-store", credentials: "include" });
    if (res.status === 401) { location.href = "../login/login.php"; return; }
    if (res.status === 403) { showMsg("このページにアクセスする権限がありません"); return; }
    if (!res.ok) { showMsg("権限情報の読み込みに失敗しました"); return; }
    const data = await res.json();
    const permissionLabels = (data.display_master?.koumoku ?? [])
        .filter(row => row.system_key === "permission");
    permissionLabels.forEach((row, index) => {
        const flag = ["system_flg", "create_account_flg", "account_flg", "update_confirmation_flg", "practice_flg", "game_flg", "event_flg"][index];
        const label = row.display_name;
        if (!flag) return;
        const input = permissionFieldset.querySelector(`input[name="${flag}"]`);
        if (!input) return;
        const labelElement = input.closest("label");
        if (labelElement) labelElement.replaceChildren(input, document.createTextNode(` ${label}`));
    });
    const createButton = permissionFieldset.closest("form")?.querySelector('button[type="submit"]');
    const createName = (data.display_master?.button ?? []).find(row => row.system_key === "register");
    if (createButton && createName) createButton.textContent = createName.display_name;
    permissionFieldset.disabled = false;
    if (data.me?.is_system !== true) { systemFlag.checked = false; systemFlag.disabled = true; }
}
loadPermissions();
