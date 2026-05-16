const API_URL = "game_api.php";

const grid = document.getElementById("gameGrid");

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
        modalText.append(document.createElement("br"));

    }

}


function closeModal() {
    modal.style.display = "none";
}

modal.addEventListener("click", closeModal);

modal.querySelector(".modal-content")
    .addEventListener("click", (e) => e.stopPropagation());

modalClose.addEventListener("click", closeModal);



async function loadgames() {

    try {

        const res = await fetch(API_URL, { cache: "no-store" });

        if (!res.ok) throw new Error();

        const games = await res.json();

        grid.innerHTML = "";

        if (!games || games.length === 0) {

            grid.innerHTML =
                `<p class="no-game">
現在、公開中の投稿はありません
</p>`;

            return;

        }


        for (const e of games) {

            const card = document.createElement("div");
            card.className = "game-card";


            const h3 = document.createElement("h3");
            h3.textContent = e.title ?? "";


            const imgBox = document.createElement("div");
            imgBox.className = "game-images";


            if (e.images) {

                for (const img of e.images) {

                    const box = document.createElement("div");
                    box.className = "photo-box";


                    const image = document.createElement("img");
                    image.src = img.image;


                    image.addEventListener(
                        "click",
                        () => openModal(img.image, e.title, e.description)
                    );


                    const comment = document.createElement("p");
                    comment.className = "photo-comment";
                    comment.textContent = img.comment ?? "";


                    box.append(image, comment);

                    imgBox.appendChild(box);

                }

            }


            const p = document.createElement("p");

            for (const line of lines(e.description)) {

                p.append(line);
                p.append(document.createElement("br"));

            }


            const small = document.createElement("small");
            small.textContent = `投稿日：${e.created_at ?? ""}`;


            card.append(h3, p, imgBox, small);

            grid.appendChild(card);

        }

    }
    catch (err) {

        console.error(err);

        grid.innerHTML =
            `<p class="no-game">
読み込みに失敗しました
</p>`;

    }

}

loadgames();