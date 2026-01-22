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
    return `
  <div class="part-card">
    <div class="part-title">参加者 ${i}</div>

    <div class="part-grid">
      <label class="field">
        <span class="label">名前（任意）</span>
        <input type="text" name="participants[${i}][name]" maxlength="100" placeholder="例）西塾 花子" />
      </label>

      <label class="field">
        <span class="label">区分 <b class="req">必須</b></span>
        <select name="participants[${i}][category]" required onchange="onCategoryChange(this)">
          <option value="幼児">幼児</option>
          <option value="小学生">小学生</option>
          <option value="中学生">中学生</option>
        </select>
      </label>

      <!-- ✅ 学年：JSが作り直すので空で用意 -->
      <label class="field grade-field" style="display:none;">
        <span class="label">学年 <b class="req">必須</b></span>
        <select name="participants[${i}][grade]">
          <option value="">選択してください</option>
        </select>
      </label>

      <label class="field">
        <span class="label">性別 <b class="req">必須</b></span>
        <!-- ✅ 必須なら「未回答(空)」は置かない -->
        <select name="participants[${i}][gender]" required>
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

function fillGradeOptions(gradeSelect, max) {
    gradeSelect.innerHTML = `<option value="">選択してください</option>`;
    for (let y = 1; y <= max; y++) {
        gradeSelect.insertAdjacentHTML("beforeend", `<option value="${y}年生">${y}年生</option>`);
    }
}

function onCategoryChange(select) {
    const card = select.closest(".part-card");
    if (!card) return;

    const gradeField = card.querySelector(".grade-field");
    const gradeSelect = gradeField?.querySelector('select[name$="[grade]"]');
    if (!gradeField || !gradeSelect) return;

    const category = select.value;

    if (category === "小学生") {
        gradeField.style.display = "";
        gradeSelect.required = true;
        fillGradeOptions(gradeSelect, 6);
    } else if (category === "中学生") {
        gradeField.style.display = "";
        gradeSelect.required = true;
        fillGradeOptions(gradeSelect, 3);
    } else {
        // 幼児：学年不要
        gradeField.style.display = "none";
        gradeSelect.required = false;
        gradeSelect.innerHTML = `<option value="">選択してください</option>`;
        gradeSelect.value = "";
    }
}

function rebuildParticipants() {
    const n = Math.max(1, Math.min(10, Number(countSelect.value || 1)));
    participantsArea.innerHTML = "";

    for (let i = 1; i <= n; i++) {
        participantsArea.insertAdjacentHTML("beforeend", participantHTML(i));
    }

    // ✅ 初期状態を反映（初期は幼児なので学年は非表示）
    participantsArea
        .querySelectorAll('select[name$="[category]"]')
        .forEach((sel) => onCategoryChange(sel));
}

countSelect.addEventListener("change", rebuildParticipants);

resetBtn.addEventListener("click", () => {
    setMsg("");
    // reset直後はDOMが元に戻るので、次tickで作り直す
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

        // ✅ 送信成功 → サンクスページへ
        const name = encodeURIComponent(fd.get("guardian_name") || "");
        window.location.href = `thanks.html?name=${name}`;
        return;
    } catch (err) {
        setMsg("通信エラーです。時間を置いて再度お試しください。", false);
    } finally {
        sending = false;
        btn.disabled = false;
        btn.textContent = "送信する";
    }
});

// ✅ HTMLのonchangeから呼ぶためにグローバル公開
window.onCategoryChange = onCategoryChange;
