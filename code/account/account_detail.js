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

    try {
        const res = await fetch(
            `account_detail_api.php?user_id=${encodeURIComponent(userId)}`,
            {
                cache: "no-store",
                credentials: "include",
            }
        );

        if (res.status === 401) {
            location.href = "../login/login.php";
            return;
        }

        if (res.status === 403) {
            showError("このユーザーの権限を変更できません");
            return;
        }

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.user || !data.permission_labels) {
            showError("権限情報を読み込めませんでした");
            return;
        }

        summary.textContent = `${data.user.name}（${data.user.login_id}）`;
        document.getElementById("userId").value = data.user.id;

        const saveButton = form.querySelector('button[type="submit"]');
        const saveName = (data.display_master?.button ?? [])
            .find(row => row.system_key === "account_detail");

        if (saveButton && saveName) {
            saveButton.textContent = saveName.display_name;
        }

        const accountMessages = (data.display_master?.message ?? [])
            .filter(row => row.system_key === "account_detail");

        if (accountMessages[0]) {
            confirmSaveMessage = accountMessages[0].display_name;
        }

        list.replaceChildren();

        for (const [flag, logicalName] of Object.entries(data.permission_labels)) {
            const label = document.createElement("label");
            const input = document.createElement("input");
            const span = document.createElement("span");

            input.type = "checkbox";
            input.name = flag;
            input.value = "1";
            input.checked = Number(data.user[flag]) === 1;
            input.disabled = flag === "system_flg" && data.me?.is_system !== true;

            span.textContent = logicalName || flag;

            label.append(input, span);
            list.append(label);
        }

        message.hidden = true;
        summary.hidden = false;
        form.hidden = false;
    } catch (error) {
        showError("権限情報を読み込めませんでした");
    }
}

form.addEventListener("submit", (event) => {
    if (!confirm(confirmSaveMessage)) {
        event.preventDefault();
    }
});

loadDetail();