// transfer.js（丸ごと置き換え）

const userTable = document.getElementById("userTable").getElementsByTagName("tbody")[0];
const msgEl = document.getElementById("msg");

// ✅ API（同じフォルダにある前提）
const API = "./photo_role_transfer_api.php";

// ユーザー一覧を取得（GENERALのみ返ってくる想定）
async function loadUsers() {
    try {
        const res = await fetch(API, {
            method: "GET",
            credentials: "same-origin",
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            showMsg(data?.error || "ユーザー情報の取得に失敗しました。", "err");
            return;
        }

        renderUsers(data.users || []);
    } catch (error) {
        showMsg("ユーザー情報の取得に失敗しました。", "err");
    }
}

// ユーザー情報をテーブルに表示
function renderUsers(users) {
    userTable.innerHTML = "";

    const roleNameMap = { 1: "SYSTEM", 2: "ADMIN", 3: "PHOTO", 4: "USER" };

    users.forEach((user) => {
        const row = document.createElement("tr");

        // 氏名（APIは name を返す）
        const nameCell = document.createElement("td");
        nameCell.textContent = user.name ?? "";
        row.appendChild(nameCell);

        // 現在の役職（APIは role_id を返す）
        const roleCell = document.createElement("td");
        roleCell.textContent = roleNameMap[user.role_id] ?? String(user.role_id ?? "");
        row.appendChild(roleCell);

        // 移行先役職：今回は「相手をPHOTOにする」固定でOK
        const selectCell = document.createElement("td");
        const select = document.createElement("select");

        const opt = document.createElement("option");
        opt.value = "PHOTO";
        opt.textContent = "PHOTO";
        select.appendChild(opt);

        selectCell.appendChild(select);
        row.appendChild(selectCell);

        // 操作（権限移行ボタン）
        const actionCell = document.createElement("td");
        const moveBtn = document.createElement("button");
        moveBtn.className = "btn";
        moveBtn.textContent = "権限移行";
        moveBtn.addEventListener("click", () => moveRole(user.id));
        actionCell.appendChild(moveBtn);
        row.appendChild(actionCell);

        userTable.appendChild(row);
    });

    // 0件のときの表示
    if (users.length === 0) {
        const row = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = 4;
        td.textContent = "移行できる一般ユーザーがいません。";
        row.appendChild(td);
        userTable.appendChild(row);
    }
}

// 権限を移行（PHOTO→相手、実行者はUSERへ）
async function moveRole(userId) {
    try {
        const res = await fetch(API, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ userId }),
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            showMsg(data?.error || "権限移行に失敗しました。", "err");
            return;
        }

        showMsg("権限が正常に移行されました。", "ok");

        // 移行後は自分がUSERになるので、この画面の権限がなくなる想定
        // → 一覧再取得すると403になることが多いから、メッセージだけ出しておく
        // loadUsers();
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

// 初回読み込み
loadUsers();
