const API_URL = "event_api.php";

const grid = document.getElementById("eventGrid");
const modal = document.getElementById("modal");
const modalTitle = document.getElementById("modal-title");
const modalImg = document.getElementById("modal-img");
const modalText = document.getElementById("modal-text");
const modalClose = document.getElementById("modal-close");

function lines(s) {
    return String(s ?? "").split("\n");
}

function openModal(imgSrc, title, desc) {
    modal.style.display = "flex";
    modalTitle.textContent = title ?? "";
    modalImg.src = imgSrc ?? "";
    modalText.innerHTML = "";
    for (const line of lines(desc)) {
        modalText.append(document.createTextNode(line));
        modalText.append(document.createElement("br"));
    }
}

function closeModal() {
    modal.style.display = "none";
}

modal.addEventListener("click", closeModal);
modal.querySelector(".modal-content").addEventListener("click", (e) => e.stopPropagation());
modalClose.addEventListener("click", closeModal);

async function loadEvents() {
    try {
        const res = await fetch(API_URL, { cache: "no-store" });
        if (!res.ok) throw new Error("fetch failed");

        const events = await res.json();
        grid.innerHTML = "";

        if (!events || events.length === 0) {
            const p = document.createElement("p");
            p.className = "no-event";
            p.textContent = "現在、公開中のイベントはありません。";
            grid.appendChild(p);
            return;
        }

        for (const e of events) {
            const card = document.createElement("div");
            card.className = "event-card";

            const h3 = document.createElement("h3");
            h3.textContent = e.title ?? "";

            const img = document.createElement("img");
            img.src = e.image_url ?? "";
            img.alt = "イベント画像";
            img.addEventListener("click", () => openModal(e.image_url, e.title, e.description));

            const p = document.createElement("p");
            p.innerHTML = "";
            for (const line of lines(e.description)) {
                p.append(document.createTextNode(line));
                p.append(document.createElement("br"));
            }

            const small = document.createElement("small");
            small.textContent = `投稿日：${e.created_at ?? ""}`;

            card.append(h3, img, p, small);
            grid.appendChild(card);
        }
    } catch (err) {
        console.error(err);
        grid.innerHTML = `<p class="no-event">読み込みに失敗しました。</p>`;
    }
}

loadEvents();
