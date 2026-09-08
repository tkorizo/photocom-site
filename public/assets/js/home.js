(function () {
    var wrap = document.querySelector('[data-hero]');
    if (!wrap) return;

    var video = wrap.querySelector('video.hero-video-el');
    var hasImage = !!wrap.querySelector('.hero-image-el');
    var desktop = wrap.getAttribute('data-hero-video-desktop') || '';
    var mobile = wrap.getAttribute('data-hero-video-mobile') || '';
    var timeoutMs = parseInt(wrap.getAttribute('data-hero-timeout') || '10000', 10);
    var loop = wrap.getAttribute('data-hero-loop') !== '0';
    var src = window.matchMedia('(max-width: 900px)').matches
        ? (mobile || desktop)
        : (desktop || mobile);

    function connectionIsSlow() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return true;
        }
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!conn) return false;
        if (conn.saveData) return true;
        var type = String(conn.effectiveType || '');
        return type === 'slow-2g' || type === '2g';
    }

    if (!video || !src) return;
    if (hasImage && connectionIsSlow()) return;

    var settled = false;
    var timer = null;

    function abortHeroVideo() {
        if (settled || !hasImage) return;
        settled = true;
        if (timer) window.clearTimeout(timer);
        video.pause();
        video.removeAttribute('src');
        video.querySelectorAll('source').forEach(function (el) { el.remove(); });
        try { video.load(); } catch (e) {}
        video.classList.remove('is-ready');
    }

    function markReady() {
        if (settled) return;
        settled = true;
        if (timer) window.clearTimeout(timer);
        video.classList.add('is-ready');
    }

    if (hasImage) {
        timer = window.setTimeout(abortHeroVideo, timeoutMs);
    } else {
        video.classList.add('is-ready');
    }

    video.muted = true;
    video.defaultMuted = true;
    video.loop = loop;
    video.playsInline = true;
    video.setAttribute('muted', '');
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    if (loop) video.setAttribute('loop', '');
    else video.removeAttribute('loop');
    video.preload = 'auto';
    video.src = src;
    video.addEventListener('playing', markReady, { once: true });
    video.addEventListener('error', abortHeroVideo, { once: true });
    if (loop) {
        video.addEventListener('ended', function () {
            video.currentTime = 0;
            video.play().catch(function () {});
        });
        document.addEventListener('visibilitychange', function () {
            if (document.hidden || !video.classList.contains('is-ready')) return;
            if (video.paused) video.play().catch(function () {});
        });
    }
    video.load();

    var tryPlay = function () {
        var playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {
                if (hasImage) abortHeroVideo();
            });
        }
    };
    if (video.readyState >= 2) tryPlay();
    else video.addEventListener('loadeddata', tryPlay, { once: true });
})();

(function () {
    var carousel = document.querySelector('[data-category-carousel]');
    if (!carousel) return;

    var track = carousel.querySelector('.category-carousel-track');
    var prev = document.querySelector('[data-carousel-prev]');
    var next = document.querySelector('[data-carousel-next]');
    var scrollAmount = 320;

    function scrollBy(delta) {
        track.scrollBy({ left: delta, behavior: 'smooth' });
    }

    if (prev) prev.addEventListener('click', function () { scrollBy(-scrollAmount); });
    if (next) next.addEventListener('click', function () { scrollBy(scrollAmount); });
})();

(function () {
    document.querySelectorAll('[data-youtube-embed]').forEach(function (frame) {
        var embed = frame.getAttribute('data-youtube-embed') || '';
        if (!embed) return;
        var play = frame.querySelector('.blog-video-play');
        if (!play) return;
        play.addEventListener('click', function () {
            var iframe = document.createElement('iframe');
            iframe.src = embed + (embed.indexOf('?') === -1 ? '?autoplay=1' : '&autoplay=1');
            iframe.title = play.getAttribute('aria-label') || 'Vidéo YouTube';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            iframe.allowFullscreen = true;
            iframe.setAttribute('allowfullscreen', '');
            frame.classList.add('is-playing');
            frame.appendChild(iframe);
        });
    });
})();
