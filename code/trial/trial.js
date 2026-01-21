const form = document.getElementById("trialForm");
const msg = document.getElementById("msg");
const btn = document.getElementById("submitBtn");
const resetBtn = document.getElementById("resetBtn");
const countSelect = document.getElementById("countSelect");
const participantsArea = document.getElementById("participants");

let sending = false;

function setMsg(text, ok = false) {
    msg.textContent = text || "";
    msg.classList.toggle("ok", ok);
    msg.classList.toggle("ng", !ok && !!text);
}

function participantHTML(i) {
    // i: 1..n
    return `
  <div class="part-card">
    <div class="part-title">参加者 ${i}</div>
    <div class="part-grid">
      <label class="field">
        <span class="label">名前 <b class="req">必須</b></span>
        <input type="text" name="participants[${i}][name]" required maxlength="100" placeholder="例）西塾 花子" />
      </label>

      <label class="field">
        <span class="label">区分 <b class="req">必須</b></span>
        <select name="participants[${i}][category]" required>
          <option value="幼児">幼児</option>
          <option value="小学生">小学生</option>
          <option value="中学生">中学生</option>
        </select>
      </label>

      <label class="field">
        <span class="label">学年（必須）</span>
        <input type="text" name="participants[${i}][grade]" required maxlength="30" placeholder="例）年中 / 小3 / 中1" />
      </label>

      <label class="field">
        <span class="label">性別（任意）</span>
        <select name="participants[${i}][gender]">
          <option value="">未回答</option>
          <option value="男">男</option>
          <option value="女">女</option>
        </select>
      </label>

      <label class="field">
        <span class="label">柔道経験 <b class="req">必須</b></span>
        <select name="participants[${i}][experience]" required>
          <option value="未経験">未経験</option>
          <option value="経験あり">経験あり</option>
        </select>
      </label>

      <label class="field">
        <span class="label">経験年数（任意）</span>
        <input type="text" name="participants[${i}][exp_years]" maxlength="30" placeholder="例）1年 / 3ヶ月" />
      </label>
    </div>
  </div>
  `;
}

function rebuildParticipants() {
    const n = Math.max(1, Math.min(10, Number(countSelect.value || 1)));
    participantsArea.innerHTML = "";
    for (let i = 1; i <= n; i++) {
        participantsArea.insertAdjacentHTML("beforeend", participantHTML(i));
    }
}

countSelect.addEventListener("change", rebuildParticipants);
resetBtn.addEventListener("click", () => {
    setMsg("");
    setTimeout(rebuildParticipants, 0);
});

// 初期生成
rebuildParticipants();

form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (sending) return;

    setMsg("");
    sending = true;
    btn.disabled = true;
    btn.textContent = "送信中…";

    try {
        const fd = new FormData(form);

        const res = await fetch("send.php", {
            method: "POST",
            body: fd,
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.ok !== true) {
            const err = data?.error ?? "unknown";
            const detail = data?.detail ? ` / ${data.detail}` : "";
            setMsg(`送信失敗: ${err}${detail}`, false);
            return;
        }

        setMsg("送信しました！担当よりご連絡します。", true);
        form.reset();
        rebuildParticipants();

    } catch (err) {
        setMsg("通信エラーです。時間を置いて再度お試しください。", false);
    } finally {
        sending = false;
        btn.disabled = false;
        btn.textContent = "送信する";
    }
});
