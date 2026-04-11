// ============================================================
// Time Cube – shared scripts (based on CleanBoard template)
// ============================================================

// Mobile sidebar toggle
const menuBtn = document.getElementById("menu-btn");
const sidebar = document.getElementById("sidebar");
const backdrop = document.getElementById("sidebar-backdrop");

if (menuBtn && sidebar && backdrop) {
    function openSidebar() { sidebar.classList.add("open"); backdrop.classList.add("show"); }
    function closeSidebar() { sidebar.classList.remove("open"); backdrop.classList.remove("show"); }
    menuBtn.addEventListener("click", () => sidebar.classList.contains("open") ? closeSidebar() : openSidebar());
    backdrop.addEventListener("click", closeSidebar);
}
