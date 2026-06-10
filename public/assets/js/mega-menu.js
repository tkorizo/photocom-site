(function () {
    var nav = document.querySelector('[data-mega-nav]');
    var stage = document.querySelector('[data-mega-stage]');
    var backdrop = document.querySelector('[data-mega-backdrop]');
    var toggle = document.querySelector('.mega-menu-toggle');
    if (!nav || !stage) return;

    var triggers = nav.querySelectorAll('[data-mega-trigger]');
    var panels = stage.querySelectorAll('[data-mega-panel]');
    var items = nav.querySelectorAll('[data-mega-item]');
    var closeTimer = null;
    var activeIndex = null;

    var isMobile = function () {
        return window.matchMedia('(max-width: 900px)').matches;
    };

    function clearTimer() {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
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

    function openMenu(index) {
        clearTimer();
        activeIndex = index;
        nav.classList.add('is-open');
        stage.hidden = false;
        if (backdrop) {
            backdrop.hidden = false;
            backdrop.setAttribute('aria-hidden', 'false');
        }
        setExpanded(index);
    }

    function closeMenu() {
        clearTimer();
        activeIndex = null;
        nav.classList.remove('is-open');
        stage.hidden = true;
        if (backdrop) {
            backdrop.hidden = true;
            backdrop.setAttribute('aria-hidden', 'true');
        }
        setExpanded(null);
    }

    function scheduleClose() {
        if (isMobile()) return;
        clearTimer();
        closeTimer = setTimeout(closeMenu, 180);
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

    if (backdrop) {
        backdrop.addEventListener('click', closeMenu);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
            nav.classList.remove('is-mobile-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
    });

    if (toggle) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-mobile-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open) closeMenu();
        });
    }

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            nav.classList.remove('is-mobile-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        } else {
            closeMenu();
        }
    });
})();
