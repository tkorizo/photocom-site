document.querySelectorAll('[data-cat-picker]').forEach(function (picker) {
    var chipsEl = picker.querySelector('[data-cat-chips]');
    var searchEl = picker.querySelector('[data-cat-search]');
    var checkboxes = picker.querySelectorAll('input[name="category_ids[]"]');

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function updateChips() {
        var selected = [];
        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                selected.push({
                    id: cb.value,
                    name: cb.dataset.catName || '',
                    parent: cb.dataset.catParent || '',
                });
            }
        });

        if (!selected.length) {
            chipsEl.className = 'cat-picker-chips is-empty';
            chipsEl.innerHTML = '<span class="cat-chip-placeholder">Aucune catégorie — ouvrez une section ci-dessous</span>';
            return;
        }

        chipsEl.className = 'cat-picker-chips';
        chipsEl.innerHTML = selected.map(function (item) {
            var parent = item.parent
                ? '<small>' + escapeHtml(item.parent) + ' ›</small> '
                : '';
            return '<span class="cat-chip" data-cat-id="' + item.id + '">' + parent + escapeHtml(item.name) + '</span>';
        }).join('');
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateChips);
    });

    if (searchEl) {
        searchEl.addEventListener('input', function () {
            var query = searchEl.value.trim().toLowerCase();

            picker.querySelectorAll('.cat-accordion-item, .cat-accordion-leaf').forEach(function (group) {
                if (!query) {
                    group.hidden = false;
                    return;
                }

                var groupName = (group.dataset.catGroup || '').toLowerCase();
                var options = group.querySelectorAll('.cat-option');
                var matchGroup = groupName.includes(query);
                var matchChild = false;

                options.forEach(function (opt) {
                    var name = (opt.dataset.catName || opt.textContent || '').toLowerCase();
                    var show = matchGroup || name.includes(query);
                    opt.hidden = !show;
                    if (show) matchChild = true;
                });

                group.hidden = !matchGroup && !matchChild;

                if (group.matches('details') && matchChild) {
                    group.open = true;
                }
            });
        });
    }

    updateChips();
});
