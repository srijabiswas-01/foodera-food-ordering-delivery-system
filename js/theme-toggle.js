(function () {
    const storageKey = 'foodera-theme';
    const root = document.documentElement;
    const navLinks = document.querySelector('.site-nav-links') || document.querySelector('.nav-links');
    const navToggle = document.querySelector('.nav-toggle');

    function preferredTheme() {
        const savedTheme = localStorage.getItem(storageKey);
        if (savedTheme) {
            return savedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        const browserTheme = document.querySelector('meta[name="theme-color"]');
        if (browserTheme) browserTheme.setAttribute('content', theme === 'dark' ? '#18202b' : getComputedStyle(root).getPropertyValue('--primary').trim() || '#ff3d00');
        document.querySelectorAll('.theme-toggle i').forEach(function (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
        });
    }

    function ensureToggle() {
        if (!navLinks || document.getElementById('theme-toggle')) {
            return;
        }

        const item = document.createElement('li');
        item.innerHTML = '<button class="theme-toggle" type="button" id="theme-toggle" aria-label="Toggle dark mode"><i class="bi bi-moon" aria-hidden="true"></i></button>';
        navLinks.appendChild(item);
    }

    ensureToggle();
    applyTheme(root.getAttribute('data-theme') || preferredTheme());

    document.querySelectorAll('.theme-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const currentTheme = root.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            localStorage.setItem(storageKey, nextTheme);
            applyTheme(nextTheme);
        });
    });

    if (navLinks && navToggle) {
        navToggle.addEventListener('click', function () {
            const isOpen = navLinks.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            navToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
            navToggle.querySelector('i').className = isOpen ? 'bi bi-x-lg' : 'bi bi-list';
        });

        navLinks.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navLinks.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', 'Open menu');
                navToggle.querySelector('i').className = 'bi bi-list';
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 820) {
                navLinks.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', 'Open menu');
                navToggle.querySelector('i').className = 'bi bi-list';
            }
        });
    }
})();
