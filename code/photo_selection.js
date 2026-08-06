function setupPhotoSelector(imageInput, previewArea) {
    let items = [];

    function move(index, offset) {
        const target = index + offset;
        if (target < 0 || target >= items.length) return;
        [items[index], items[target]] = [items[target], items[index]];
        render();
    }

    function chooseReplacement(index) {
        const picker = document.createElement("input");
        picker.type = "file";
        picker.accept = "image/*";
        picker.onchange = () => {
            const file = picker.files[0];
            if (!file) return;
            const oldId = items[index].id || items[index].replaceId || null;
            items[index] = { file, comment: items[index].comment, replaceId: oldId };
            render();
        };
        picker.click();
    }

    function render() {
        previewArea.innerHTML = "";
        items.forEach((item, index) => {
            const card = document.createElement("div");
            card.className = "selected-photo-card";

            const image = document.createElement("img");
            image.className = "selected-photo-preview";
            image.alt = `選択写真 ${index + 1}`;
            if (item.file) {
                const objectUrl = URL.createObjectURL(item.file);
                image.src = objectUrl;
                image.onload = () => URL.revokeObjectURL(objectUrl);
            } else {
                image.src = item.url;
            }

            const title = document.createElement("strong");
            title.textContent = `${index + 1}番目：${item.file ? item.file.name : "登録済み写真"}`;

            const controls = document.createElement("div");
            controls.className = "selected-photo-controls";
            [
                ["← 前へ", () => move(index, -1), index === 0],
                ["後ろへ →", () => move(index, 1), index === items.length - 1],
                ["写真を変更", () => chooseReplacement(index), false],
                ["写真を削除", () => { items.splice(index, 1); render(); }, false]
            ].forEach(([label, handler, disabled]) => {
                const button = document.createElement("button");
                button.type = "button";
                button.textContent = label;
                button.disabled = disabled;
                button.onclick = handler;
                controls.appendChild(button);
            });

            const label = document.createElement("label");
            label.textContent = `写真${index + 1} コメント`;
            const textarea = document.createElement("textarea");
            textarea.rows = 2;
            textarea.value = item.comment || "";
            textarea.oninput = () => { item.comment = textarea.value; };

            card.append(image, title, controls, label, textarea);
            previewArea.appendChild(card);
        });
    }

    imageInput.addEventListener("change", () => {
        const newItems = Array.from(imageInput.files).map(file => ({ file, comment: "", replaceId: null }));
        items.push(...newItems);
        imageInput.value = "";
        render();
    });

    return {
        clear() {
            items = [];
            imageInput.value = "";
            render();
        },
        loadExisting(images) {
            items = (images || []).map(image => ({
                id: Number(image.id),
                url: image.image,
                comment: image.comment || ""
            }));
            render();
        },
        count() {
            return items.length;
        },
        appendTo(formData) {
            const manifest = [];
            items.forEach(item => {
                if (item.file) {
                    const fileIndex = manifest.filter(entry => entry.kind === "new").length;
                    formData.append("new_images[]", item.file, item.file.name);
                    manifest.push({ kind: "new", fileIndex, replaceId: item.replaceId || null, comment: item.comment || "" });
                } else {
                    manifest.push({ kind: "existing", id: item.id, comment: item.comment || "" });
                }
            });
            formData.append("photo_manifest", JSON.stringify(manifest));
        }
    };
}
