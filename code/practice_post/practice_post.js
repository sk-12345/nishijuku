const API_URL = "practice_post_api.php";

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("practiceGrid");

const imageInput = document.getElementById("imageInput");
const commentArea = document.getElementById("imageCommentArea");


const photoSelector = setupPhotoSelector(imageInput, commentArea);
let editingId = null;
let loadedItems = [];


function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;");
}


function renderPractices(practices, canDelete) {

    grid.innerHTML = practices.map(e => {

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

            `<button type="button" class="edit-btn" data-edit-id="${e.id}">編集</button>
<form data-delete-form data-id="${e.id}">
<button type="submit"
class="delete-btn">
削除
</button>
</form>`: "";


        return `

<div class="practice-card">

<h3>${escapeHtml(e.title)}</h3>

${imagesHTML}

<div class="practice-description">
${escapeHtml(e.description).replaceAll("\n", "<br>")}
</div>

<small>
投稿日：${e.created_at}
</small>

${delBtn}

</div>

`;

    }).join("");

    document.querySelectorAll("[data-delete-form]").forEach(form => {

        form.addEventListener("submit", async ev => {

            ev.preventDefault();

            const id = form.getAttribute("data-id");

            if (!confirm("削除しますか？")) return;

            const fd = new FormData();

            fd.append("action", "delete");
            fd.append("delete_id", id);

            await fetch(API_URL, {
                method: "POST",
                body: fd
            });

            load();

        });

    });

    document.querySelectorAll("[data-edit-id]").forEach(button => {
        button.addEventListener("click", () => {
            const item = loadedItems.find(row => Number(row.id) === Number(button.dataset.editId));
            if (!item) return;
            editingId = item.id;
            postForm.elements.title.value = item.title || "";
            postForm.elements.description.value = item.description || "";
            photoSelector.loadExisting(item.images);
            postForm.querySelector(".post-btn").textContent = "変更を保存";
            postMsg.textContent = "編集中です";
            postArea.scrollIntoView({ behavior: "smooth" });
        });
    });

}


async function load() {

    const res = await fetch(API_URL);

    const data = await res.json();
    loadedItems = data.practices || [];

    postArea.style.display = data.me?.can_post ? "block" : "none";

    renderPractices(data.practices, data.me?.can_delete);

}


postForm.addEventListener("submit", async ev => {

    ev.preventDefault();

    if (photoSelector.count() === 0) return;
    const fd = new FormData(postForm);
    fd.delete("images[]");
    fd.append("action", editingId ? "update" : "add");
    if (editingId) fd.append("id", editingId);
    photoSelector.appendTo(fd);

    await fetch(API_URL, {
        method: "POST",
        body: fd
    });

    postForm.reset();

    photoSelector.clear();

    editingId = null;
    postForm.querySelector(".post-btn").textContent = "投稿";

    load();

});


load();
