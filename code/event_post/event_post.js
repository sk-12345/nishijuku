const API_URL = "event_post_api.php";

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("eventGrid");

const imageInput = document.getElementById("imageInput");
const commentArea = document.getElementById("imageCommentArea");


const photoSelector = setupPhotoSelector(imageInput, commentArea);
let editingId = null;
let loadedEvents = [];


/* ======================
   XSS対策
====================== */

function escapeHtml(str) {

    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");

}


/* ======================
   イベント描画
====================== */

function renderEvents(events, canDelete) {

    if (!events || events.length === 0) {

        grid.innerHTML = `
<p class="no-event">
イベントはまだありません
</p>`;

        return;

    }


    grid.innerHTML = events.map(e => {

        let imagesHTML = "";

        if (e.images && e.images.length > 0) {

            imagesHTML = `
<div class="practice-images">

${e.images.map(img => `

<div class="photo-box">

<img src="${escapeHtml(img.image)}">

<p class="photo-comment">
${escapeHtml(img.comment ?? "")}
</p>

</div>

`).join("")}

</div>`;

        }


        const delBtn = canDelete ?

            `<button type="button" class="edit-btn" data-edit-id="${e.id}">編集</button>
<form data-delete-form data-id="${e.id}">
<button type="submit" class="delete-btn">
削除
</button>
</form>` : "";


        return `

<div class="event-card">

<h3>${escapeHtml(e.title)}</h3>

${imagesHTML}

<div class="event-description">
${escapeHtml(e.description).replaceAll("\n", "<br>")}
</div>

<small>
投稿日：${e.created_at ?? ""}
</small>

${delBtn}

</div>

`;

    }).join("");


    /* ======================
       削除処理
    ====================== */

    if (canDelete) {

        document.querySelectorAll("[data-edit-id]").forEach(button => {
            button.addEventListener("click", () => {
                const event = loadedEvents.find(item => Number(item.id) === Number(button.dataset.editId));
                if (!event) return;
                editingId = event.id;
                postForm.elements.title.value = event.title || "";
                postForm.elements.description.value = event.description || "";
                photoSelector.loadExisting(event.images);
                postForm.querySelector(".post-btn").textContent = "変更を保存";
                postMsg.textContent = "編集中です";
                postArea.scrollIntoView({ behavior: "smooth" });
            });
        });

        document.querySelectorAll("[data-delete-form]").forEach(form => {

            form.addEventListener("submit", async ev => {

                ev.preventDefault();

                const id = form.getAttribute("data-id");

                if (!confirm("削除しますか？")) return;

                const fd = new FormData();

                fd.append("action", "delete");
                fd.append("delete_id", id);

                const res = await fetch(API_URL, {
                    method: "POST",
                    body: fd
                });

                if (!res.ok) {

                    alert("削除に失敗しました");

                    return;

                }

                await load();

            });

        });

    }

}


/* ======================
   読み込み
====================== */

async function load() {

    try {

        const res = await fetch(API_URL, {
            cache: "no-store"
        });

        if (!res.ok) throw new Error();

        const data = await res.json();
        loadedEvents = data.events || [];

        postArea.style.display = data.me?.can_post
            ? "block"
            : "none";

        renderEvents(
            data.events,
            data.me?.can_delete
        );

    }

    catch (err) {

        console.error(err);

        grid.innerHTML = `
<p class="no-event">
読み込みに失敗しました
</p>`;

    }

}


/* ======================
   投稿処理
====================== */

postForm?.addEventListener("submit", async ev => {

    ev.preventDefault();

    postMsg.textContent = "";

    if (photoSelector.count() === 0) {
        postMsg.textContent = "写真を1枚以上選択してください";
        return;
    }
    const fd = new FormData(postForm);
    fd.delete("images[]");
    fd.append("action", editingId ? "update" : "add");
    if (editingId) fd.append("id", editingId);
    photoSelector.appendTo(fd);


    const res = await fetch(API_URL, {
        method: "POST",
        body: fd
    });


    if (!res.ok) {

        postMsg.textContent = "投稿に失敗しました";

        return;

    }


    postForm.reset();

    photoSelector.clear();

    editingId = null;
    postForm.querySelector(".post-btn").textContent = "投稿";

    postMsg.textContent = "投稿しました！";


    await load();

});


/* ======================
   初期読み込み
====================== */

load();
