(function () {
    if (window.__PHOTOCOM_FILTERS_DRAWER__) return;
    window.__PHOTOCOM_FILTERS_DRAWER__ = true;

    var drawer = document.querySelector('[data-filters-drawer]');
    var backdrop = document.querySelector('[data-filters-backdrop]');
    var openBtns = document.querySelectorAll('[data-filters-open]');
    var closeBtns = document.querySelectorAll('[data-filters-close]');
    var form = document.querySelector('[data-filters-form]');
    if (!drawer) return;

    var isMobile = function () {
        return window.matchMedia('(max-width: 900px)').matches;
    };

    function syncBodyScroll() {
        var filtersOpen = document.body.classList.contains('filters-drawer-open');
        var megaOpen = document.body.classList.contains('mega-drawer-open');
        document.body.style.overflow = (filtersOpen || megaOpen) ? 'hidden' : '';
    }

    function setBackdropVisible(visible) {
        if (!backdrop) return;
        if (visible) {
            backdrop.hidden = false;
            backdrop.setAttribute('aria-hidden', 'false');
            // force reflow for fade-in
            void backdrop.offsetWidth;
            backdrop.classList.add('is-visible');
        } else {
            backdrop.classList.remove('is-visible');
            backdrop.setAttribute('aria-hidden', 'true');
            var hide = function () {
                if (!backdrop.classList.contains('is-visible')) {
                    backdrop.hidden = true;
                }
                backdrop.removeEventListener('transitionend', hide);
            };
            backdrop.addEventListener('transitionend', hide);
            // fallback si pas de transition
            setTimeout(hide, 360);
        }
    }

    function setOpen(open) {
        if (!isMobile() && open) {
            return;
        }
        document.body.classList.toggle('filters-drawer-open', open);
        drawer.classList.toggle('is-open', open);
        drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
        openBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        setBackdropVisible(open);
        syncBodyScroll();
    }

    function openDrawer() {
        setOpen(true);
        var closeBtn = drawer.querySelector('[data-filters-close]');
        if (closeBtn && typeof closeBtn.focus === 'function') {
            setTimeout(function () { closeBtn.focus(); }, 40);
        }
    }

    function closeDrawer() {
        setOpen(false);
    }

    openBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openDrawer();
        });
    });

    closeBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeDrawer();
        });
    });

    if (backdrop) {
        backdrop.addEventListener('click', closeDrawer);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
            closeDrawer();
        }
    });

    if (form) {
        form.addEventListener('submit', function () {
            closeDrawer();
        });

        var resetLink = form.querySelector('.btn-outline-filter');
        if (resetLink) {
            resetLink.addEventListener('click', function () {
                closeDrawer();
            });
        }
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeDrawer();
        }
    });

    if (isMobile()) {
        drawer.setAttribute('aria-hidden', 'true');
    }
})();
