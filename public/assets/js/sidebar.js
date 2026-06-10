(function () {
    var sidebar = document.querySelector('[data-sidebar]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    var toggles = document.querySelectorAll('[data-sidebar-toggle]');
    var drawerToggle = document.querySelector('[data-sidebar-drawer]');
    var closeBtn = document.querySelector('[data-sidebar-close]');
    if (!sidebar) return;

    var mq = window.matchMedia('(max-width: 1024px)');
    var storageKey = 'photocom-sidebar-collapsed';

    function isMobile() {
        return mq.matches;
    }

    function setMobileOpen(open) {
        document.body.classList.toggle('sidebar-mobile-open', open);
        sidebar.classList.toggle('is-mobile-open', open);
        if (backdrop) {
            backdrop.hidden = !open;
            backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
        toggles.forEach(function (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function setDesktopCollapsed(collapsed) {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (drawerToggle) {
            drawerToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            drawerToggle.setAttribute('aria-label', collapsed ? 'Ouvrir le menu' : 'Fermer le menu');
        }
        try {
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        } catch (e) {}
    }

    function closeMobile() {
        if (isMobile()) setMobileOpen(false);
    }

    if (!isMobile()) {
        try {
            if (localStorage.getItem(storageKey) === '1') {
                setDesktopCollapsed(true);
            }
        } catch (e) {}
    }

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!isMobile()) return;
            setMobileOpen(!document.body.classList.contains('sidebar-mobile-open'));
        });
    });

    if (drawerToggle) {
        drawerToggle.addEventListener('click', function () {
            if (isMobile()) {
                setMobileOpen(!document.body.classList.contains('sidebar-mobile-open'));
                return;
            }
            setDesktopCollapsed(!document.body.classList.contains('sidebar-collapsed'));
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            if (isMobile()) {
                closeMobile();
            } else {
                setDesktopCollapsed(true);
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeMobile);
    }

    document.querySelectorAll('[data-accordion]').forEach(function (block) {
        var trigger = block.querySelector('.sidebar-accordion-trigger');
        var panel = block.querySelector('.sidebar-accordion-panel');
        if (!trigger || !panel) return;

        trigger.addEventListener('click', function () {
            var open = block.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.hidden = !open;
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobile();
    });

    mq.addEventListener('change', function () {
        if (!isMobile()) {
            setMobileOpen(false);
            document.body.style.overflow = '';
        }
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) closeMobile();
        });
    });
})();
