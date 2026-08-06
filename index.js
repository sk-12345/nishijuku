document.addEventListener("DOMContentLoaded", () => {
    setupNavigation();
    loadLatestActivity();
});

function setupNavigation() {
    const navToggle = document.getElementById("navToggle");
    const navDrawer = document.getElementById("navDrawer");
    if (!navToggle || !navDrawer) return;

    const closeDrawer = () => {
        navDrawer.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
        navToggle.setAttribute("aria-label", "メニューを開く");
    };

    navToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        const isOpen = navDrawer.classList.toggle("open");
        navToggle.setAttribute("aria-expanded", String(isOpen));
        navToggle.setAttribute("aria-label", isOpen ? "メニューを閉じる" : "メニューを開く");
    });

    navDrawer.querySelectorAll("a").forEach((link) => link.addEventListener("click", closeDrawer));
    document.addEventListener("click", (event) => {
        if (!navDrawer.classList.contains("open")) return;
        if (event.target instanceof Node && !navDrawer.contains(event.target)) closeDrawer();
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") closeDrawer();
    });
}

async function loadLatestActivity() {
    const grid = document.getElementById("latestGrid");
    if (!grid) return;

    try {
        const response = await fetch("index_api.php", { cache: "no-store" });
        if (!response.ok) throw new Error("request_failed");
        const data = await response.json();
        if (!Array.isArray(data.items)) throw new Error("invalid_response");
        grid.replaceChildren(...data.items.map(createLatestCard));
    } catch (error) {
        console.error("最新情報の読み込みに失敗しました。", error);
        const message = document.createElement("p");
        message.className = "latest-error";
        message.textContent = "最新情報を読み込めませんでした。しばらくしてから再度お試しください。";
        grid.replaceChildren(message);
    }
}

function createLatestCard(item) {
    const card = document.createElement("article");
    card.className = "latest-card";
    card.dataset.type = item.type || "";
    const media = document.createElement("div");
    media.className = "latest-image";

    if (item.image) {
        const image = document.createElement("img");
        image.src = item.image;
        image.alt = `${item.title || item.label || "活動"}の写真`;
        image.loading = "lazy";
        media.appendChild(image);
    } else {
        media.classList.add("no-image");
        media.textContent = "初柔会 西塾";
    }

    const badge = document.createElement("span");
    badge.className = "latest-badge";
    badge.textContent = item.label || "活動";
    media.appendChild(badge);
    const body = document.createElement("div");
    body.className = "latest-body";

    if (item.empty) {
        const title = document.createElement("h3");
        title.textContent = `最新の${item.label || "活動"}`;
        const description = document.createElement("p");
        description.className = "latest-description";
        description.textContent = "現在、公開中の投稿はありません。";
        body.append(title, description, createDetailLink(item.url));
    } else {
        const date = document.createElement("time");
        date.className = "latest-date";
        date.dateTime = item.date || "";
        date.textContent = formatDate(item.date);
        const title = document.createElement("h3");
        title.textContent = item.title || "タイトルなし";
        const description = document.createElement("p");
        description.className = "latest-description";
        description.textContent = item.description || "";
        body.append(date, title, description, createDetailLink(item.url));
    }

    card.append(media, body);
    return card;
}

function createDetailLink(url) {
    const link = document.createElement("a");
    link.className = "latest-link";
    link.href = url || "#";
    link.textContent = "詳しく見る";
    return link;
}

function formatDate(value) {
    if (!value) return "";
    const normalized = String(value).replace(" ", "T");
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return String(value).slice(0, 10).replaceAll("-", ".");
    return new Intl.DateTimeFormat("ja-JP", { year: "numeric", month: "2-digit", day: "2-digit" }).format(date);
}
