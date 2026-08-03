/**
 * Slider produits vedette — défilement continu droite → gauche.
 */
(function () {
    var root = document.querySelector('[data-featured-slider][data-animate="1"]');
    if (!root) return;

    var track = root.querySelector('.featured-slider-track');
    if (!track) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        root.removeAttribute('data-animate');
        return;
    }

    var paused = false;
    var offset = 0;
    var speed = 0.35; // px per frame ~21px/s

    function halfWidth() {
        // Track is duplicated — scroll through first half then reset
        return track.scrollWidth / 2;
    }

    function tick() {
        if (!paused) {
            offset += speed;
            var half = halfWidth();
            if (half > 0 && offset >= half) {
                offset -= half;
            }
            track.style.transform = 'translate3d(' + (-offset) + 'px, 0, 0)';
        }
        window.requestAnimationFrame(tick);
    }

    root.addEventListener('mouseenter', function () { paused = true; });
    root.addEventListener('mouseleave', function () { paused = false; });
    root.addEventListener('focusin', function () { paused = true; });
    root.addEventListener('focusout', function () { paused = false; });

    window.requestAnimationFrame(tick);
})();
