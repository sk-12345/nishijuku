const userTable = document.getElementById("userTable").getElementsByTagName('tbody')[0];
const submitBtn = document.getElementById("submitBtn");
const msgEl = document.getElementById("msg");

// ユーザー一覧を取得するAPI
async function loadUsers() {
    try {
        const res = await fetch("../transfer/transfer_api.php");
        const data = await res.json();

        if (!res.ok || !data.ok) {
            showMsg(data.error || "ユーザー情報の取得に失敗しました。", "err");
            return;
        }

        const users = data.users || [];
        renderUsers(users);

    } catch (error) {
        showMsg("ユーザー情報の取得に失敗しました。", "err");
    }
}

// ユーザー情報をテーブルに表示
function renderUsers(users) {
    userTable.innerHTML = ""; // 既存の行を消す

    users.forEach(user => {
        const row = document.createElement("tr");

        // 氏名
        const nameCell = document.createElement("td");
        nameCell.textContent = user.fullname;
        row.appendChild(nameCell);

        // 現在の役職
        const roleCell = document.createElement("td");
        roleCell.textContent = user.role_name;
        row.appendChild(roleCell);

        // 移行先役職（選択肢）
        const selectCell = document.createElement("td");
        const select = document.createElement("select");
        const options = ["PHOTO", "USER"];
        options.forEach(option => {
            const opt = document.createElement("option");
            opt.value = option;
            opt.textContent = option;
            select.appendChild(opt);
        });
        selectCell.appendChild(select);
        row.appendChild(selectCell);

        // 移行ボタン
        const actionCell = document.createElement("td");
        const moveBtn = document.createElement("button");
        moveBtn.className = "btn";
        moveBtn.textContent = "権限移行";
        moveBtn.addEventListener("click", () => moveRole(user.id, select.value));
        actionCell.appendChild(moveBtn);
        row.appendChild(actionCell);

        userTable.appendChild(row);
    });
}

// 権限を移行する処理
async function moveRole(userId, newRole) {
    try {
        const res = await fetch("../transfer/photo_role_transfer_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userId, newRole })
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            showMsg(data.error || "権限移行に失敗しました。", "err");
            return;
        }

        showMsg("権限が正常に移行されました。", "ok");
        loadUsers(); // 再読み込み
    } catch (error) {
        showMsg("権限移行中にエラーが発生しました。", "err");
    }
}

// メッセージ表示
function showMsg(text, type) {
    msgEl.className = "msg " + (type || "");
    msgEl.textContent = text || "";
    msgEl.style.display = text ? "block" : "none";
}

loadUsers(); // 初回読み込み
