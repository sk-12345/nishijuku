// 画面が読み込まれてから実行
document.addEventListener("DOMContentLoaded", () => {
    const navToggle = document.getElementById("navToggle");   // 三本線ボタン
    const navDrawer = document.getElementById("navDrawer");   // 下から出るメニュー

    // 念のため要素が取れなかったときは何もしない
    if (!navToggle || !navDrawer) return;

    // 三本線をクリックしたとき
    navToggle.addEventListener("click", () => {
        navDrawer.classList.toggle("open"); // ← ここで .open つけたり消したり
    });

    // メニューのリンクを押したら閉じる
    navDrawer.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", () => {
            navDrawer.classList.remove("open");
        });
    });
});
