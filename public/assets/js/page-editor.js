/**
 * Éditeur de pages éditoriales (about / contact / conseils)
 * Même interaction que l’éditeur d’accueil : structure + canvas + panneau.
 */
(function () {
    var root = document.querySelector('[data-page-builder]');
    if (!root) return;

    var meta = {};
    try {
        meta = JSON.parse(root.getAttribute('data-page-meta') || '{}');
    } catch (e) {
        meta = {};
    }

    function selectBlock(id) {
        root.querySelectorAll('.he-block').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-block') === id);
        });
        root.querySelectorAll('.he-structure-item').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-select') === id);
        });
        root.querySelectorAll('.he-panel-section').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-panel') === id);
        });
        var info = meta[id] || { title: id, desc: '' };
        var titleEl = root.querySelector('[data-panel-title]');
        var descEl = root.querySelector('[data-panel-desc]');
        if (titleEl) titleEl.textContent = info.title;
        if (descEl) descEl.textContent = info.desc;
        var block = root.querySelector('.he-block[data-block="' + id + '"]');
        if (block) block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    root.querySelectorAll('.he-block').forEach(function (block) {
        block.addEventListener('click', function () {
            selectBlock(block.getAttribute('data-block'));
        });
    });
    root.querySelectorAll('[data-select]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectBlock(btn.getAttribute('data-select'));
        });
    });

    root.querySelectorAll('[data-bind]').forEach(function (input) {
        var key = input.getAttribute('data-bind');
        input.addEventListener('input', function () {
            root.querySelectorAll('[data-preview="' + key + '"]').forEach(function (el) {
                el.textContent = input.value;
            });
        });
    });

    root.querySelectorAll('[data-toggle-block]').forEach(function (checkbox) {
        var blockName = checkbox.getAttribute('data-toggle-block');
        checkbox.addEventListener('change', function () {
            var block = root.querySelector('.he-block[data-block="' + blockName + '"]');
            if (block) block.classList.toggle('is-hidden', !checkbox.checked);
        });
    });

    // Sélection initiale
    var initial = root.querySelector('.he-structure-item.is-active') || root.querySelector('.he-structure-item');
    if (initial) selectBlock(initial.getAttribute('data-select'));
})();
