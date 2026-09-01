const API_URL = "home_api.php"; // 同じフォルダならこれでOK

const welcomeTitle = document.getElementById("welcomeTitle");
const roleText = document.getElementById("roleText");
const menuArea = document.getElementById("menuArea");

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function rowsByKey(rows, key) {
    return (rows ?? []).filter(row => row.system_key === key);
}

function buildMenu(flags, displayMaster = {}) {
    const names = rowsByKey(displayMaster.button, "home_menu");
    const definitions = [
        { enabled: flags.create_account, href: "../register/register.html", fallback: "新規アカウント作成" },
        { enabled: flags.account, href: "../account/account.html", fallback: "アカウント管理" },
        { enabled: flags.update_confirmation, href: "../audit/audit.html", fallback: "更新履歴" },
        { enabled: flags.practice, href: "../practice_post/practice_post.html", fallback: "練習風景・投稿", extraClass: "main-card" },
        { enabled: flags.game, href: "../game_post/game_post.html", fallback: "試合・投稿", extraClass: "main-card" },
        { enabled: flags.event, href: "../event_post/event_post.html", fallback: "イベント一覧・投稿", extraClass: "main-card" },
        { enabled: true, href: "../password/password.html", fallback: "パスワード変更" },
        { enabled: true, href: "../logout.php", fallback: "ログアウト", extraClass: "logout-card" },
    ];
    const items = definitions.map((item, index) => ({
        ...item,
        text: names[index]?.display_name || item.fallback,
    })).filter(item => item.enabled);

    menuArea.innerHTML = "";
    items.forEach(item => {
        const a = document.createElement("a");
        a.href = item.href;
        a.textContent = item.text;
        a.className = ["card", item.extraClass].filter(Boolean).join(" ");
        menuArea.appendChild(a);
    });
}

async function loadHome() {
    const res = await fetch(API_URL, { cache: "no-store" });

    if (res.status === 401) {
        location.href = "../login/login.php";
        return;
    }

    if (!res.ok) {
        welcomeTitle.innerHTML = "読み込みに失敗しました";
        return;
    }

    const data = await res.json();

    const fullname = data.user?.fullname ?? "";
    const roleId = data.user?.role_id ?? "";

    welcomeTitle.innerHTML = `ようこそ、<br>${escapeHtml(fullname)} さん`;
    roleText.textContent = `権限ID：${roleId}`;

    buildMenu(data.flags ?? {}, data.display_master ?? {});
}

loadHome();
