const message = document.getElementById("message");
const tableWrap = document.querySelector(".table-wrap");
const tbody = document.getElementById("auditTbody");
const searchInput = document.getElementById("searchInput");
let auditRows = [];

function addCell(row, label, value) {
    const cell = document.createElement("td");
    cell.dataset.label = label;
    cell.textContent = value ?? "";
    row.appendChild(cell);
}

function renderRows(rows) {
    tbody.innerHTML = "";

    if (rows.length === 0) {
        message.hidden = false;
        message.textContent = auditRows.length === 0
            ? "表示できる更新履歴はありません。"
            : "検索条件に一致する更新履歴はありません。";
        tableWrap.hidden = true;
        return;
    }

    rows.forEach(item => {
        const row = document.createElement("tr");
        addCell(row, "対象", item.target_type);
        addCell(row, "データ", item.target_name);
        addCell(row, "最終変更者", item.updated_by);
        addCell(row, "最終変更日時", item.updated_at);
        tbody.appendChild(row);
    });

    message.hidden = true;
    tableWrap.hidden = false;
}

function filterRows() {
    const keyword = searchInput.value.trim().toLocaleLowerCase("ja");
    if (keyword === "") {
        renderRows(auditRows);
        return;
    }

    const filtered = auditRows.filter(item =>
        [item.target_type, item.target_name, item.updated_by]
            .some(value => String(value ?? "").toLocaleLowerCase("ja").includes(keyword))
    );
    renderRows(filtered);
}

async function loadAuditLog() {
    try {
        const response = await fetch("audit_api.php", { cache: "no-store" });

        if (response.status === 401) {
            location.href = "../login/login.php";
            return;
        }
        if (response.status === 403) {
            message.textContent = "このページを閲覧する権限がありません。";
            message.classList.add("error");
            return;
        }
        if (!response.ok) throw new Error("request_failed");

        const data = await response.json();
        auditRows = data.rows ?? [];
        renderRows(auditRows);
    } catch (error) {
        message.textContent = "更新履歴の読み込みに失敗しました。";
        message.classList.add("error");
    }
}

searchInput.addEventListener("input", filterRows);
loadAuditLog();
