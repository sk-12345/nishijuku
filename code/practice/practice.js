const API_URL = "practice_api.php";

const grid = document.getElementById("practiceGrid");
const modal = document.getElementById("modal");
const modalTitle = document.getElementById("modal-title");
const modalImg = document.getElementById("modal-img");
const modalText = document.getElementById("modal-text");
const modalClose = document.getElementById("modal-close");


function lines(s) {
    return String(s ?? "").split("\n");
}


// モーダル表示
function openModal(imgSrc, title, desc) {

    modal.style.display = "flex";

    modalTitle.textContent = title ?? "";

    modalImg.src = imgSrc ?? "";

    modalText.innerHTML = "";

    for (const line of lines(desc)) {

        modalText.append(
            document.createTextNode(line)
        );

        modalText.append(
            document.createElement("br")
        );
    }
}


// 閉じる
function closeModal() {
    modal.style.display = "none";
}

modal.addEventListener("click", closeModal);

modal.querySelector(".modal-content")
    .addEventListener("click", (e) => e.stopPropagation());

modalClose.addEventListener("click", closeModal);



// =====================
// 読み込み
// =====================
async function loadpractices() {

    try {

        const res = await fetch(
            API_URL,
            { cache: "no-store" }
        );

        if (!res.ok) throw new Error();

        const practices = await res.json();

        grid.innerHTML = "";


        if (!practices || practices.length === 0) {

            grid.innerHTML =
                `<p class="no-practice">
            現在、公開中の投稿はありません。
            </p>`;

            return;
        }



        for (const e of practices) {

            const card = document.createElement("div");

            card.className = "practice-card";


            // タイトル
            const h3 = document.createElement("h3");

            h3.textContent = e.title ?? "";



            // 複数画像
            const imgBox =
                document.createElement("div");

            imgBox.className =
                "practice-images";


            if (e.images) {

                for (const imgUrl of e.images) {

                    const img =
                        document.createElement("img");

                    img.src = imgUrl;

                    img.alt = "練習画像";


                    img.addEventListener(
                        "click",
                        () => openModal(
                            imgUrl,
                            e.title,
                            e.description
                        )
                    );

                    imgBox.appendChild(img);
                }
            }



            // 説明
            const p = document.createElement("p");

            for (const line of lines(e.description)) {

                p.append(
                    document.createTextNode(line)
                );

                p.append(
                    document.createElement("br")
                );
            }


            // 日付
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
    catch (err) {

        console.error(err);

        grid.innerHTML =
            `<p class="no-practice">
        読み込みに失敗しました
        </p>`;

    }

}


loadpractices();