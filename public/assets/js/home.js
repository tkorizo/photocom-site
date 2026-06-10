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
