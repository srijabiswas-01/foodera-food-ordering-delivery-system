document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    var navToggle = document.querySelector('.admin-nav-toggle');
    var sidebar = document.querySelector('.admin-topbar');
    var backdrop = document.querySelector('.admin-nav-backdrop');
    var collapse = document.querySelector('.admin-sidebar-collapse');
    var themeToggle = document.querySelector('.admin-theme-toggle');
    var root = document.documentElement;

    function setDrawer(open) {
        if (!navToggle || !sidebar) return;
        sidebar.classList.toggle('is-open', open);
        if (backdrop) backdrop.classList.toggle('is-open', open);
        body.classList.toggle('admin-sidebar-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        navToggle.querySelector('i').className = open ? 'bi bi-x-lg' : 'bi bi-list';
    }

    function setCollapsed(collapsed) {
        body.classList.toggle('admin-sidebar-collapsed', collapsed);
        if (collapse) {
            collapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            collapse.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            collapse.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            collapse.querySelector('i').className = collapsed ? 'bi bi-layout-sidebar-inset-reverse' : 'bi bi-layout-sidebar-inset';
        }
        try { localStorage.setItem('foodera-admin-sidebar-collapsed', collapsed ? '1' : '0'); } catch (error) {}
    }

    function setTheme(theme) {
        var dark = theme === 'dark';
        root.dataset.adminTheme = dark ? 'dark' : 'light';
        var browserTheme = document.querySelector('meta[name="theme-color"]');
        if (browserTheme) browserTheme.setAttribute('content', dark ? '#181d23' : '#ff3d00');
        if (themeToggle) {
            themeToggle.setAttribute('aria-label', dark ? 'Use light mode' : 'Use dark mode');
            themeToggle.setAttribute('title', dark ? 'Use light mode' : 'Use dark mode');
            themeToggle.querySelector('i').className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
        try { localStorage.setItem('foodera-admin-theme', theme); } catch (error) {}
    }

    try { setCollapsed(localStorage.getItem('foodera-admin-sidebar-collapsed') === '1'); } catch (error) { setCollapsed(false); }
    setTheme(root.dataset.adminTheme === 'dark' ? 'dark' : 'light');

    if (navToggle) navToggle.addEventListener('click', function () { setDrawer(!sidebar.classList.contains('is-open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setDrawer(false); });
    if (collapse) collapse.addEventListener('click', function () { setCollapsed(!body.classList.contains('admin-sidebar-collapsed')); });
    if (themeToggle) themeToggle.addEventListener('click', function () { setTheme(root.dataset.adminTheme === 'dark' ? 'light' : 'dark'); });
    document.querySelectorAll('.admin-nav a').forEach(function (link) { link.addEventListener('click', function () { setDrawer(false); }); });
});
