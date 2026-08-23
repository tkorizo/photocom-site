(function () {
    if (window.__PHOTOCOM_MEGA_MENU__) return;
    window.__PHOTOCOM_MEGA_MENU__ = true;

    var nav = document.querySelector('[data-mega-nav]');
    var stage = document.querySelector('[data-mega-stage]');
    var stageInner = stage ? stage.querySelector('.container') || stage : null;
    var backdrop = document.querySelector('[data-mega-backdrop]');
    var toggle = document.querySelector('.mega-menu-toggle');
    var mobileClose = document.querySelector('[data-mega-close]');
    if (!nav || !stage) return;

    var triggers = nav.querySelectorAll('[data-mega-trigger]');
    var panels = stage.querySelectorAll('[data-mega-panel]');
    var items = nav.querySelectorAll('[data-mega-item]');
    var closeTimer = null;
    var activeIndex = null;
    var animTimer = null;

    var isMobile = function () {
        return window.matchMedia('(max-width: 900px)').matches;
    };

    function clearTimer() {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
    }

    function syncBodyScroll() {
        var filtersOpen = document.body.classList.contains('filters-drawer-open');
        var megaOpen = document.body.classList.contains('mega-drawer-open');
        document.body.style.overflow = (filtersOpen || megaOpen) ? 'hidden' : '';
    }

    function restorePanels() {
        panels.forEach(function (panel) {
            if (stageInner && panel.parentElement !== stageInner) {
                stageInner.appendChild(panel);
            }
            panel.hidden = true;
            panel.classList.remove('is-active', 'is-accordion');
        });
    }

    function placePanelForMobile(index) {
        var panel = stageInner
            ? stageInner.querySelector('[data-mega-panel="' + index + '"]')
            : null;
        if (!panel) {
            panel = nav.querySelector('[data-mega-panel="' + index + '"]');
        }
        if (!panel) return null;

        var item = nav.querySelector('[data-mega-item][data-mega-index="' + index + '"]');
        if (item && panel.parentElement !== item) {
            item.appendChild(panel);
        }
        panel.classList.add('is-accordion');
        return panel;
    }

    function setExpanded(index) {
        triggers.forEach(function (trigger) {
            var open = index !== null && trigger.getAttribute('data-mega-trigger') === String(index);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        items.forEach(function (item) {
            item.classList.toggle('is-active', index !== null && item.getAttribute('data-mega-index') === String(index));
        });

        panels.forEach(function (panel) {
            var show = index !== null && panel.getAttribute('data-mega-panel') === String(index);
            panel.hidden = !show;
            panel.classList.toggle('is-active', show);
        });
    }

    function setBackdrop(visible) {
        if (!backdrop) return;
        var currentlyVisible = !backdrop.hidden;
        if (currentlyVisible === visible) return;
        backdrop.hidden = !visible;
        backdrop.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function pulseStageAnim() {
        if (isMobile()) return;
        stage.classList.remove('is-animating');
        // Force reflow so the animation can restart on first open only
        void stage.offsetWidth;
        stage.classList.add('is-animating');
        if (animTimer) clearTimeout(animTimer);
        animTimer = setTimeout(function () {
            stage.classList.remove('is-animating');
            animTimer = null;
        }, 200);
    }

    function setMobileDrawer(open) {
        nav.classList.toggle('is-mobile-open', open);
        document.body.classList.toggle('mega-drawer-open', open);
        nav.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        }
        if (isMobile()) {
            setBackdrop(open);
        }
        syncBodyScroll();
    }

    function openMenu(index) {
        clearTimer();
        var alreadyOpen = nav.classList.contains('is-open') && !isMobile();
        var samePanel = alreadyOpen && activeIndex === index;
        activeIndex = index;
        nav.classList.add('is-open');

        if (isMobile()) {
            restorePanels();
            placePanelForMobile(index);
            stage.hidden = true;
            setBackdrop(true);
            setExpanded(index);
            var item = nav.querySelector('[data-mega-item][data-mega-index="' + index + '"]');
            if (item && typeof item.scrollIntoView === 'function') {
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
            return;
        }

        if (!alreadyOpen) {
            restorePanels();
            stage.hidden = false;
            setBackdrop(true);
            pulseStageAnim();
        }
        if (!samePanel) {
            setExpanded(index);
        }
    }

    function closeMenu() {
        clearTimer();
        activeIndex = null;
        nav.classList.remove('is-open');
        stage.classList.remove('is-animating');
        restorePanels();
        stage.hidden = true;
        if (!isMobile() || !nav.classList.contains('is-mobile-open')) {
            setBackdrop(false);
        }
        setExpanded(null);
    }

    function closeMobileDrawer() {
        closeMenu();
        setMobileDrawer(false);
    }

    function scheduleClose() {
        if (isMobile()) return;
        clearTimer();
        closeTimer = setTimeout(closeMenu, 280);
    }

    triggers.forEach(function (trigger) {
        var index = trigger.getAttribute('data-mega-trigger');

        trigger.addEventListener('mouseenter', function () {
            if (isMobile()) return;
            openMenu(index);
        });

        trigger.addEventListener('focus', function () {
            if (isMobile()) return;
            openMenu(index);
        });

        trigger.addEventListener('click', function (e) {
            if (!isMobile()) return;
            e.preventDefault();
            if (activeIndex === index && nav.classList.contains('is-open')) {
                closeMenu();
            } else {
                openMenu(index);
            }
        });
    });

    nav.addEventListener('mouseenter', clearTimer);
    nav.addEventListener('mouseleave', scheduleClose);
    stage.addEventListener('mouseenter', clearTimer);
    stage.addEventListener('mouseleave', scheduleClose);

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            if (isMobile()) {
                closeMobileDrawer();
            } else {
                closeMenu();
            }
        });
        // Le voile ne doit jamais voler le hover du menu desktop
        backdrop.addEventListener('mouseenter', function () {
            if (!isMobile()) scheduleClose();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (isMobile() && nav.classList.contains('is-mobile-open')) {
            closeMobileDrawer();
        } else if (!isMobile()) {
            closeMenu();
        }
    });

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (!isMobile()) return;
            if (nav.classList.contains('is-mobile-open')) {
                closeMobileDrawer();
            } else {
                setMobileDrawer(true);
            }
        });
    }

    if (mobileClose) {
        mobileClose.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileDrawer();
        });
    }

    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobile()) {
                closeMobileDrawer();
            }
        });
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            setMobileDrawer(false);
            restorePanels();
            closeMenu();
            syncBodyScroll();
        } else if (nav.classList.contains('is-open')) {
            closeMenu();
        }
    });

    if (isMobile()) {
        nav.setAttribute('aria-hidden', 'true');
    }
})();
