(function () {
    var banner = document.querySelector('[data-cookie-banner]');
    if (!banner) return;

    var key = (window.__COOKIE_BANNER__ && window.__COOKIE_BANNER__.storageKey) || 'photocom_cookie_consent';
    var existing = null;
    try {
        existing = localStorage.getItem(key);
    } catch (e) {
        existing = null;
    }

    if (existing === 'accepted' || existing === 'refused') {
        return;
    }

    banner.hidden = false;

    function save(value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {}
        banner.hidden = true;
    }

    var accept = banner.querySelector('[data-cookie-accept]');
    var refuse = banner.querySelector('[data-cookie-refuse]');
    if (accept) accept.addEventListener('click', function () { save('accepted'); });
    if (refuse) refuse.addEventListener('click', function () { save('refused'); });
})();
