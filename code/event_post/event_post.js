const API_URL = "event_post_api.php";

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("eventGrid");

const imageInput = document.getElementById("imageInput");
const commentArea = document.getElementById("imageCommentArea");


imageInput.addEventListener("change", () => {

    commentArea.innerHTML = "";

    const files = imageInput.files;

    for (let i = 0; i < files.length; i++) {

        const div = document.createElement("div");

        div.innerHTML = `
<label>写真${i + 1} コメント</label>
<textarea name="image_comments[]"
rows="2"></textarea>
`;

        commentArea.appendChild(div);

    }

});


function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");
}


function renderEvents(events, canDelete) {

    grid.innerHTML = events.map(e => {

        let imagesHTML = "";

        if (e.images) {

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
<button type="submit"
class="delete-btn">
削除
</button>
</form>`: "";


        return `

<div class="event-card">

<h3>${escapeHtml(e.title)}</h3>

${imagesHTML}

<div class="event-description">
${escapeHtml(e.description).replaceAll("\n", "<br>")}
</div>

<small>
投稿日：${e.created_at}
</small>

${delBtn}

</div>

`;

    }).join("");

}


async function load() {

    const res = await fetch(API_URL);

    const data = await res.json();

    postArea.style.display = data.me?.can_post ? "block" : "none";

    renderEvents(data.events, data.me?.can_delete);

}


postForm.addEventListener("submit", async ev => {

    ev.preventDefault();

    const fd = new FormData(postForm);

    fd.append("action", "add");

    await fetch(API_URL, {
        method: "POST",
        body: fd
    });

    postForm.reset();

    commentArea.innerHTML = "";

    load();

});

load();