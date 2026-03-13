const API_URL = "event_post_api.php";

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("eventGrid");

const imageInput = document.getElementById("imageInput");
const commentArea = document.getElementById("imageCommentArea");


/* ======================
   写真コメント入力生成
====================== */

imageInput.addEventListener("change", () => {

    commentArea.innerHTML = "";

    const files = imageInput.files;

    for (let i = 0; i < files.length; i++) {

        const div = document.createElement("div");

        div.innerHTML = `
<label>写真${i + 1} コメント</label>
<textarea name="image_comments[]" rows="2"></textarea>
`;

        commentArea.appendChild(div);

    }

});


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

            `<form data-delete-form data-id="${e.id}">
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

    const fd = new FormData(postForm);

    fd.append("action", "add");


    const res = await fetch(API_URL, {
        method: "POST",
        body: fd
    });


    if (!res.ok) {

        postMsg.textContent = "投稿に失敗しました";

        return;

    }


    postForm.reset();

    commentArea.innerHTML = "";

    postMsg.textContent = "投稿しました！";


    await load();

});


/* ======================
   初期読み込み
====================== */

load();