const API_URL = "home_api.php";

const welcomeTitle = document.getElementById("welcomeTitle");
const roleText = document.getElementById("roleText");
const menuArea = document.getElementById("menuArea");

function buildMenu(flags, displayMaster = {}) {
    const buttonNames = Object.fromEntries(
        (displayMaster.button ?? []).map(row => [
            row.system_key,
            row.display_name
        ])
    );

    const definitions = [
        {
            enabled: flags.create_account,
            href: "../register/register.html",
            buttonId: "common_shinkiAccountSakusei"
        },
        {
            enabled: flags.account,
            href: "../account/account.html",
            buttonId: "common_accountKanri"
        },
        {
            enabled: flags.update_confirmation,
            href: "../audit/audit.html",
            buttonId: "common_koshinRireki"
        },
        {
            enabled: flags.practice,
            href: "../practice_post/practice_post.html",
            buttonId: "common_renshuHukeiToko",
            extraClass: "main-card"
        },
        {
            enabled: flags.game,
            href: "../game_post/game_post.html",
            buttonId: "common_shiaiTouko",
            extraClass: "main-card"
        },
        {
            enabled: flags.event,
            href: "../event_post/event_post.html",
            buttonId: "common_eventTouko",
            extraClass: "main-card"
        },
        {
            enabled: true,
            href: "../password/password.html",
            buttonId: "common_passHenko"
        },
        {
            enabled: true,
            href: "../logout.php",
            buttonId: "common_logout",
            extraClass: "logout-card"
        }
    ];

    menuArea.replaceChildren();

    definitions
        .filter(item => item.enabled)
        .forEach(item => {
            const a = document.createElement("a");

            a.href = item.href;
            a.textContent = buttonNames[item.buttonId] ?? item.buttonId;
            a.className = ["card", item.extraClass]
                .filter(Boolean)
                .join(" ");

            menuArea.appendChild(a);
        });
}

async function loadHome() {
    if (!welcomeTitle || !roleText || !menuArea) {
        console.error(
            "welcomeTitle、roleText、menuArea のいずれかが見つかりません。"
        );
        return;
    }

    try {
        const res = await fetch(API_URL, {
            cache: "no-store"
        });

        if (res.status === 401) {
            location.href = "../login/login.html";
            return;
        }

        if (!res.ok) {
            throw new Error(`読み込みに失敗しました（HTTP ${res.status}）`);
        }

        const data = await res.json();

        const fullname = data.user?.fullname ?? "";
        const roleId = data.user?.role_id ?? "";

        // 名前をHTMLとして解釈させず、文字として表示します。
        welcomeTitle.replaceChildren(
            document.createTextNode("ようこそ、"),
            document.createElement("br"),
            document.createTextNode(`${fullname} さん`)
        );

        roleText.textContent = `権限ID：${roleId}`;

        buildMenu(data.flags ?? {}, data.display_master ?? {});
    } catch (error) {
        console.error("ホーム画面の読み込みエラー:", error);

        welcomeTitle.textContent = "読み込みに失敗しました";
        roleText.textContent = "";
        menuArea.replaceChildren();
    }
}

// HTMLの読み込みが完了してから実行します。
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", loadHome, { once: true });
} else {
    loadHome();
}