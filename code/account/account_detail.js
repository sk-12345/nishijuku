const fallbackPermissionLabels = {
    system_flg: "システム",
    create_account_flg: "アカウント作成",
    account_flg: "アカウント管理",
    update_confirmation_flg: "更新履歴",
    practice_flg: "練習",
    game_flg: "試合",
    event_flg: "イベント",
};

const params = new URLSearchParams(location.search);
const userId = params.get("user_id");
const form = document.getElementById("permissionForm");
const list = document.getElementById("permissionList");
const summary = document.getElementById("userSummary");
const message = document.getElementById("message");
let confirmSaveMessage = "権限設定を保存しますか？";

function showError(text) {
    message.textContent = text;
    message.hidden = false;
    summary.hidden = true;
    form.hidden = true;
}

async function loadDetail() {
    if (!userId || !/^\d+$/.test(userId)) {
        showError("対象ユーザーが不正です");
        return;
    }
    const res = await fetch(`account_detail_api.php?user_id=${encodeURIComponent(userId)}`, {
        cache: "no-store",
        credentials: "include",
    });
    if (res.status === 401) {
        location.href = "../login/login.php";
        return;
    }
    if (res.status === 403) {
        showError("このユーザーの権限を変更できません");
        return;
    }
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.user) {
        showError("権限情報を読み込めませんでした");
        return;
    }

    summary.textContent = `${data.user.name}（${data.user.login_id}）`;
    document.getElementById("userId").value = data.user.id;
    const masterLabels = (data.display_master?.koumoku ?? [])
        .filter(row => row.system_key === "permission")
        .map(row => row.display_name);
    const saveButton = form.querySelector('button[type="submit"]');
    const saveName = (data.display_master?.button ?? []).find(row => row.system_key === "account_detail");
    if (saveButton && saveName) saveButton.textContent = saveName.display_name;
    const accountMessages = (data.display_master?.message ?? []).filter(row => row.system_key === "account_detail");
    if (accountMessages[0]) confirmSaveMessage = accountMessages[0].display_name;
    list.innerHTML = Object.entries(fallbackPermissionLabels).map(([flag, fallback], index) => {
        const label = masterLabels[index] || fallback;
        const checked = Number(data.user[flag]) === 1 ? "checked" : "";
        const disabled = flag === "system_flg" && data.me?.is_system !== true ? "disabled" : "";
        return `<label><input type="checkbox" name="${flag}" value="1" ${checked} ${disabled}><span>${label}</span></label>`;
    }).join("");
    form.hidden = false;
}

form.addEventListener("submit", (event) => {
    if (!confirm(confirmSaveMessage)) event.preventDefault();
});

loadDetail();
