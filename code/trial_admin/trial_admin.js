const API_URL = "list_api.php";
const DELETE_API_URL = "delete_api.php";

const tbody = document.getElementById("tbody");
const msgEl = document.getElementById("msg");
const emptyEl = document.getElementById("empty");
const qEl = document.getElementById("q");
const reloadBtn = document.getElementById("reloadBtn");

let allRows = [];

function esc(str) {
    return String(str ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function showMsg(text, type) {
    msgEl.className = "msg " + (type || "");
    msgEl.textContent = text || "";
    msgEl.style.display = text ? "block" : "none";
}

function render(rows) {
    tbody.innerHTML = "";

    if (!rows || rows.length === 0) {
        emptyEl.style.display = "block";
        return;
    }
    emptyEl.style.display = "none";

    tbody.innerHTML = rows.map(r => `
    <tr>
      <td>${esc(r.created_at)}</td>
      <td>${esc(r.name)}</td>
      <td>${esc(r.phone)}</td>
      <td>${esc(r.email)}</td>
      <td>${esc(r.age || "")}</td>
      <td>${esc(r.preferred_date || "")}</td>
      <td><pre>${esc(r.message || "")}</pre></td>
      <td>
        <button class="btn btn-del" data-id="${esc(r.id)}">削除</button>
      </td>
    </tr>
  `).join("");

    document.querySelectorAll(".btn-del").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            deleteRow(id);
        });
    });
}

function applyFilter() {
    const q = (qEl.value || "").trim().toLowerCase();
    if (!q) return render(allRows);

    const filtered = allRows.filter(r => {
        const blob = `${r.name ?? ""} ${r.phone ?? ""} ${r.email ?? ""}`.toLowerCase();
        return blob.includes(q);
    });
    render(filtered);
}

async function load() {
    showMsg("", "");
    reloadBtn.disabled = true;
    reloadBtn.textContent = "更新中…";

    try {
        const res = await fetch(API_URL, { credentials: "same-origin" });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data?.error || "取得に失敗しました");
        }

        allRows = data.rows || [];
        render(allRows);
    } catch (e) {
        showMsg(e?.message || "取得に失敗しました", "err");
    } finally {
        reloadBtn.disabled = false;
        reloadBtn.textContent = "更新";
    }
}

async function deleteRow(id) {
    if (!id) return;
    if (!confirm("この応募を削除しますか？（元に戻せません）")) return;

    showMsg("", "");

    try {
        const res = await fetch(DELETE_API_URL, {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            throw new Error(data?.error || "削除に失敗しました");
        }

        showMsg("削除しました", "ok");
        await load();
        applyFilter(); // 検索中でも反映
    } catch (e) {
        showMsg(e?.message || "削除に失敗しました", "err");
    }
}

qEl.addEventListener("input", applyFilter);
reloadBtn.addEventListener("click", load);

load();
