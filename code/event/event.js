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


function openModal(img, title, desc) {

    modal.style.display = "flex";

    modalTitle.textContent = title ?? "";

    modalImg.src = img ?? "";

    modalText.innerHTML = "";

    for (const line of lines(desc)) {

        modalText.append(line);

        modalText.append(
            document.createElement("br")
        );

    }

}


modal.addEventListener(
    "click",
    () => modal.style.display = "none"
);

modalClose.addEventListener(
    "click",
    () => modal.style.display = "none"
);


async function load() {

    const res = await fetch(
        API_URL,
        { cache: "no-store" }
    );

    const events = await res.json();

    grid.innerHTML = "";

    if (!events || events.length === 0) {

        grid.innerHTML =
            `<p class="no-event">
現在イベントはありません
</p>`;

        return;
    }


    for (const e of events) {

        const card = document.createElement("div");

        card.className = "event-card";


        const h3 = document.createElement("h3");

        h3.textContent = e.title ?? "";


        const imgBox = document.createElement("div");

        imgBox.className = "practice-images";


        if (e.images) {

            for (const img of e.images) {

                const box = document.createElement("div");

                box.className = "photo-box";

                const im = document.createElement("img");

                im.src = img.image;

                im.addEventListener(
                    "click",
                    () => openModal(
                        img.image,
                        e.title,
                        img.comment ?? e.description
                    )
                );

                const c = document.createElement("div");

                c.className = "photo-comment";

                c.textContent = img.comment ?? "";

                box.append(im, c);

                imgBox.appendChild(box);

            }

        }


        const p = document.createElement("p");

        for (const line of lines(e.description)) {

            p.append(line);

            p.append(
                document.createElement("br")
            );

        }


        const small = document.createElement("small");

        small.textContent =
            `投稿日：${e.created_at ?? ""}`;


        card.append(
            h3,
            imgBox,
            p,
            small
        );

        grid.appendChild(card);

    }

}

load();