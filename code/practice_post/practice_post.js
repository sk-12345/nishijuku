const API_URL = "practice_post_api.php"; // 同じフォルダ想定

const postArea = document.getElementById("postArea");
const postForm = document.getElementById("postForm");
const postMsg = document.getElementById("postMsg");
const grid = document.getElementById("practiceGrid");

function escapeHtml(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function renderpractices(practices, canDelete) {
    if (!practices || practices.length === 0) {
        grid.innerHTML = `<p class="no-practice">イベントがありません。</p>`;
        return;
    }

    grid.innerHTML = practices.map(e => {
        const delForm = canDelete ? `
      <form data-delete-form data-id="${escapeHtml(e.id)}">
        <button type="submit" class="delete-btn">削除</button>
      </form>
    ` : ``;

        return `
      <div class="practice-card">
        <h3>${escapeHtml(e.title)}</h3>
        <img src="${escapeHtml(e.image_url)}" alt="イベント画像">
        <p>${escapeHtml(e.description).replaceAll("\n", "<br>")}</p>
        <small>投稿日：${escapeHtml(e.created_at)}</small>
        ${delForm}
      </div>
    `;
    }).join("");

    // 削除イベント
    if (canDelete) {
        document.querySelectorAll("[data-delete-form]").forEach(form => {
            form.addpracticeListener("submit", async (ev) => {
                ev.prpracticeDefault();
                const id = form.getAttribute("data-id");
                if (!confirm("削除しますか？")) return;

                const fd = new FormData();
                fd.append("action", "delete");
                fd.append("delete_id", id);

                const res = await fetch(API_URL, { method: "POST", body: fd });
                if (!res.ok) {
                    alert("削除に失敗しました");
                    return;
                }
                await load(); // 再読み込み
            });
        });
    }
}

async function load() {
    const res = await fetch(API_URL, { cache: "no-store" });

    // 未ログインならログインへ
    if (res.status === 401) {
        location.href = "../login/login.php";
        return;
    }

    const data = await res.json();

    // 投稿フォーム表示制御
    if (data.me?.can_post) {
        postArea.style.display = "block";
    } else {
        postArea.style.display = "none";
    }

    renderpractices(data.practices, !!data.me?.can_delete);
}

// 投稿
postForm?.addpracticeListener("submit", async (ev) => {
    ev.prpracticeDefault();
    postMsg.textContent = "";

    const fd = new FormData(postForm);
    fd.append("action", "add");

    const res = await fetch(API_URL, { method: "POST", body: fd });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        postMsg.textContent = "投稿に失敗しました";
        console.error(err);
        return;
    }

    postForm.reset();
    postMsg.textContent = "投稿しました！";
    await load();
});

load();
