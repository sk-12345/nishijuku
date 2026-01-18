const form = document.getElementById("trialForm");
const msgEl = document.getElementById("msg");
const btn = document.getElementById("submitBtn");

// 連打防止：送信中フラグ + 送信後クールダウン(ms)
let isSubmitting = false;
const COOLDOWN_MS = 1500;

function showMsg(text, type) {
    msgEl.className = "msg " + (type || "");
    msgEl.textContent = text || "";
    msgEl.style.display = text ? "block" : "none";
}

function normalizePhone(s) {
    // 数字と + 以外を除去（ハイフンやスペースを消す）
    return String(s || "").replace(/[^\d+]/g, "");
}

function isValidEmail(email) {
    // ブラウザのtype=email + サーバ側でも検証する前提で、ここは軽め
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phoneNormalized) {
    // 日本の一般的な電話を想定：数字だけで 10〜11桁（+81形式も許容）
    // +81XXXXXXXXX(先頭0無し) もあり得るので 9〜12くらいまで許容しつつ、
    // 基本は 10/11 を推奨
    const p = phoneNormalized;

    // +81形式
    if (p.startsWith("+81")) {
        const digits = p.replace(/\D/g, "");
        // "+81"込みで数字は 11〜12程度になることが多い（例: +819012345678 -> 12桁）
        return digits.length >= 11 && digits.length <= 13;
    }

    // 国内形式（数字のみ想定）
    const digits = p.replace(/\D/g, "");
    return digits.length === 10 || digits.length === 11;
}

function disableButton(text) {
    btn.disabled = true;
    btn.textContent = text;
}

function enableButton() {
    btn.disabled = false;
    btn.textContent = "送信する";
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();
    showMsg("", "");

    // 二重送信防止
    if (isSubmitting) {
        showMsg("送信中です。少し待ってください。", "err");
        return;
    }

    const fd = new FormData(form);

    // 必須値
    const name = (fd.get("name") || "").toString().trim();
    const phoneRaw = (fd.get("phone") || "").toString().trim();
    const email = (fd.get("email") || "").toString().trim();

    if (!name || !phoneRaw || !email) {
        showMsg("必須項目（お名前・電話番号・メール）を入力してください。", "err");
        return;
    }

    // メール軽チェック（最終はサーバでもチェック）
    if (!isValidEmail(email)) {
        showMsg("メールアドレスの形式が正しくありません。", "err");
        return;
    }

    // 電話番号：正規化してチェック
    const phoneNormalized = normalizePhone(phoneRaw);

    if (!isValidPhone(phoneNormalized)) {
        showMsg("電話番号の形式が正しくありません。（例：090-1234-5678）", "err");
        return;
    }

    // サーバへ渡す値をセット
    fd.set("name", name);
    fd.set("phone", phoneRaw); // 表示そのまま
    fd.set("phone_normalized", phoneNormalized); // 整形済み
    fd.set("email", email);

    // 送信開始
    isSubmitting = true;
    disableButton("送信中…");

    try {
        const res = await fetch("submit.php", {
            method: "POST",
            body: fd,
            credentials: "same-origin",
            cache: "no-store",
        });

        const data = await res.json().catch(() => null);

        // JSONが返ってこない（PHPエラー等）
        if (!data) {
            throw new Error("送信に失敗しました。時間をおいて再度お試しください。");
        }

        // 400/500でも error を表示
        if (!res.ok || !data.ok) {
            showMsg(data.error || "入力内容を確認してください。", "err");
            return;
        }

        // 成功
        location.href = "thanks.html";
    } catch (err) {
        showMsg(err?.message || "送信に失敗しました。", "err");
    } finally {
        // すぐ押せないように少し待ってから戻す（連打対策）
        setTimeout(() => {
            isSubmitting = false;
            enableButton();
        }, COOLDOWN_MS);
    }
});
