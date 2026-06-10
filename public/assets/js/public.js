(function () {
    var slider = document.querySelector('[data-hero-slider]');
    if (!slider) return;

    var slides = slider.querySelectorAll('.hero-slide');
    var dots = document.querySelectorAll('[data-hero-dot]');
    var prev = document.querySelector('[data-hero-prev]');
    var next = document.querySelector('[data-hero-next]');
    var current = 0;
    var timer;

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (s, i) {
            s.classList.toggle('is-active', i === current);
        });
        dots.forEach(function (d, i) {
            d.classList.toggle('is-active', i === current);
        });
    }

    function nextSlide() { show(current + 1); }
    function prevSlide() { show(current - 1); }

    function startAutoplay() {
        clearInterval(timer);
        timer = setInterval(nextSlide, 6000);
    }

    if (prev) prev.addEventListener('click', function () { prevSlide(); startAutoplay(); });
    if (next) next.addEventListener('click', function () { nextSlide(); startAutoplay(); });

    dots.forEach(function (dot, i) {
        dot.addEventListener('click', function () {
            show(i);
            startAutoplay();
        });
    });

    if (slides.length > 1) startAutoplay();
})();
