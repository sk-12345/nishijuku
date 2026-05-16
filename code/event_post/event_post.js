const API_URL = "event_post_api.php";

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("eventGrid");

const imageInput = document.getElementById("imageInput");
const previewArea = document.getElementById("imagePreviewArea");
const submitBtn = document.getElementById("submitBtn");
const editId = document.getElementById("editId");
const cancelEditBtn = document.getElementById("cancelEditBtn");

let selectedFiles = [];
let currentEvents = [];
let editMode = false;
let editingEventId = null;

let existingImages = [];
let deleteImageIds = [];

imageInput.addEventListener("change", () => {
    selectedFiles = Array.from(imageInput.files);

    if (editMode) {
        renderEditImages();
    } else {
        renderPreview();
    }
});

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function renderPreview() {
    previewArea.innerHTML = "";

    selectedFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);

        const div = document.createElement("div");
        div.className = "preview-item";

        div.innerHTML = `
            <div class="preview-row">
                <div class="preview-box">
                    <img src="${url}">
                </div>

                <div class="order-area">
                    <label>表示順</label>
                    <input type="number"
                           name="display_orders[]"
                           value="${index + 1}"
                           min="1">

                    <button type="button" class="order-btn" onclick="moveNewDown(${index})">↓</button>
                    <button type="button" class="order-btn" onclick="moveNewUp(${index})">↑</button>

                    <br>

                    <button type="button"
                            class="remove-btn"
                            onclick="removeNewImage(${index})">
                        選択解除
                    </button>
                </div>
            </div>

            <label>写真${index + 1} コメント</label>
            <textarea name="image_comments[]" rows="3"></textarea>
        `;

        previewArea.appendChild(div);
    });
}

function renderEditImages() {
    previewArea.innerHTML = "";

    existingImages.forEach((img, index) => {
        img.display_order = index + 1;

        const div = document.createElement("div");
        div.className = "preview-item";

        div.innerHTML = `
            <input type="hidden"
                   name="existing_image_ids[]"
                   value="${escapeHtml(img.id)}">

            <div class="preview-row">
                <div class="preview-box">
                    <img src="${escapeHtml(img.image)}">
                </div>

                <div class="order-area">
                    <label>表示順</label>
                    <input type="number"
                           name="existing_display_orders[]"
                           value="${index + 1}"
                           min="1">

                    <button type="button" class="order-btn" onclick="moveExistingDown(${index})">↓</button>
                    <button type="button" class="order-btn" onclick="moveExistingUp(${index})">↑</button>

                    <br>

                    <button type="button"
                            class="remove-btn"
                            onclick="removeExistingImage(${index})">
                        選択解除
                    </button>
                </div>
            </div>

            <label>登録済み写真${index + 1} コメント</label>
            <textarea name="existing_image_comments[]" rows="3">${escapeHtml(img.comment)}</textarea>
        `;

        previewArea.appendChild(div);
    });

    selectedFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const no = existingImages.length + index + 1;

        const div = document.createElement("div");
        div.className = "preview-item";

        div.innerHTML = `
            <div class="preview-row">
                <div class="preview-box">
                    <img src="${url}">
                </div>

                <div class="order-area">
                    <label>表示順</label>
                    <input type="number"
                           name="new_display_orders[]"
                           value="${no}"
                           min="1">

                    <button type="button" class="order-btn" onclick="moveNewDown(${index})">↓</button>
                    <button type="button" class="order-btn" onclick="moveNewUp(${index})">↑</button>

                    <br>

                    <button type="button"
                            class="remove-btn"
                            onclick="removeNewImage(${index})">
                        選択解除
                    </button>
                </div>
            </div>

            <label>新規写真${index + 1} コメント</label>
            <textarea name="new_image_comments[]" rows="3"></textarea>
        `;

        previewArea.appendChild(div);
    });
}

function moveExistingUp(index) {
    if (index <= 0) return;

    [existingImages[index - 1], existingImages[index]] =
        [existingImages[index], existingImages[index - 1]];

    renderEditImages();
}

function moveExistingDown(index) {
    if (index >= existingImages.length - 1) return;

    [existingImages[index + 1], existingImages[index]] =
        [existingImages[index], existingImages[index + 1]];

    renderEditImages();
}

function removeExistingImage(index) {
    deleteImageIds.push(existingImages[index].id);
    existingImages.splice(index, 1);
    renderEditImages();
}

function moveNewUp(index) {
    if (index <= 0) return;

    [selectedFiles[index - 1], selectedFiles[index]] =
        [selectedFiles[index], selectedFiles[index - 1]];

    editMode ? renderEditImages() : renderPreview();
}

function moveNewDown(index) {
    if (index >= selectedFiles.length - 1) return;

    [selectedFiles[index + 1], selectedFiles[index]] =
        [selectedFiles[index], selectedFiles[index + 1]];

    editMode ? renderEditImages() : renderPreview();
}

function removeNewImage(index) {
    selectedFiles.splice(index, 1);
    editMode ? renderEditImages() : renderPreview();
}

function startEdit(id) {
    const event = currentEvents.find(e => Number(e.id) === Number(id));

    if (!event) {
        alert("編集データが見つかりません。");
        return;
    }

    editMode = true;
    editingEventId = event.id;

    postForm.title.value = event.title;
    postForm.description.value = event.description;

    existingImages = (event.images ?? []).map(img => ({
        id: img.id,
        image: img.image,
        image_path: img.image_path,
        comment: img.comment ?? "",
        display_order: img.display_order ?? 1
    }));

    deleteImageIds = [];
    selectedFiles = [];

    editId.value = event.id;
    submitBtn.textContent = "編集";
    cancelEditBtn.style.display = "block";
    imageInput.required = false;
    imageInput.value = "";

    renderEditImages();

    postArea.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
}

function resetFormMode() {
    editMode = false;
    editingEventId = null;

    selectedFiles = [];
    existingImages = [];
    deleteImageIds = [];

    editId.value = "";
    submitBtn.textContent = "投稿";
    cancelEditBtn.style.display = "none";

    imageInput.required = true;
    imageInput.value = "";

    previewArea.innerHTML = "";
    postForm.reset();
}

cancelEditBtn.addEventListener("click", () => {
    resetFormMode();
});

function renderEvents(events, canDelete) {
    if (!events || events.length === 0) {
        grid.innerHTML = `
<p class="no-event">
イベントはまだありません
</p>`;
        return;
    }

    grid.innerHTML = events.map(e => {
        const imagesHTML = `
<div class="event-images">
${(e.images ?? []).map(img => `
<div class="photo-box">
<img src="${escapeHtml(img.image)}">
<p class="photo-comment">
${escapeHtml(img.comment ?? "")}
</p>
</div>
`).join("")}
</div>`;

        const deleteBtn = canDelete ? `
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

<button type="button"
        class="edit-btn"
        onclick="startEdit(${e.id})">
編集
</button>

${deleteBtn}

</div>`;
    }).join("");

    if (canDelete) {
        document.querySelectorAll("[data-delete-form]").forEach(form => {
            form.addEventListener("submit", async ev => {
                ev.preventDefault();

                if (!confirm("削除しますか？")) return;

                const fd = new FormData();
                fd.append("action", "delete");
                fd.append("delete_id", form.dataset.id);

                const res = await fetch(API_URL, {
                    method: "POST",
                    body: fd
                });

                if (!res.ok) {
                    alert("削除に失敗しました");
                    return;
                }

                resetFormMode();
                await load();
            });
        });
    }
}

async function load() {
    try {
        const res = await fetch(API_URL, {
            cache: "no-store"
        });

        if (!res.ok) throw new Error();

        const data = await res.json();

        currentEvents = data.events ?? [];

        postArea.style.display = data.me?.can_post ? "block" : "none";

        renderEvents(
            currentEvents,
            data.me?.can_delete
        );

    } catch (err) {
        console.error(err);

        grid.innerHTML = `
<p class="no-event">
読み込みに失敗しました
</p>`;
    }
}

postForm?.addEventListener("submit", async ev => {
    ev.preventDefault();

    postMsg.textContent = "";

    const fd = new FormData(postForm);

    // input[type=file] から自動で入った画像を削除（二重投稿防止）
    fd.delete("images[]");
    fd.delete("new_images[]");

    if (editMode) {
        fd.append("action", "edit");
        fd.append("id", editingEventId);

        deleteImageIds.forEach(id => {
            fd.append("delete_image_ids[]", id);
        });

        selectedFiles.forEach(file => {
            fd.append("new_images[]", file);
        });

    } else {
        fd.append("action", "add");

        selectedFiles.forEach(file => {
            fd.append("images[]", file);
        });
    }

    const res = await fetch(API_URL, {
        method: "POST",
        body: fd
    });

    const data = await res.json();

    if (!res.ok || !data.ok) {
        postMsg.textContent = "保存に失敗しました";
        console.log(data);
        return;
    }

    const msg = editMode ? "編集しました！" : "投稿しました！";

    resetFormMode();
    postMsg.textContent = msg;

    await load();
});

load();