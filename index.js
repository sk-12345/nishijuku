document.addEventListener("DOMContentLoaded", () => {
    const navToggle = document.getElementById("navToggle");
    const navDrawer = document.getElementById("navDrawer");

    if (!navToggle || !navDrawer) return;

    function openDrawer() {
        navDrawer.classList.add("open");
        navToggle.setAttribute("aria-expanded", "true");
    }

    function closeDrawer() {
        navDrawer.classList.remove("open");
        navToggle.setAttribute("aria-expanded", "false");
    }

    function toggleDrawer() {
        if (navDrawer.classList.contains("open")) {
            closeDrawer();
        } else {
            openDrawer();
        }
    }

    navToggle.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleDrawer();
    });

    // ドロワー内リンククリックで閉じる
    navDrawer.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            closeDrawer();
        });
    });

    // 外側クリックで閉じる
    document.addEventListener("click", (e) => {
        if (!navDrawer.classList.contains("open")) return;
        const target = e.target;
        if (target instanceof Element) {
            const clickedInside = navDrawer.contains(target) || navToggle.contains(target);
            if (!clickedInside) closeDrawer();
        }
    });

    // Escで閉じる
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeDrawer();
    });
});
