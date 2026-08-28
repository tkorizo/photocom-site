/**
 * Autosuggest recherche produits (header, filtres catalogue, admin).
 */
(function () {
    'use strict';

    if (window.__PHOTOCOM_SEARCH_SUGGEST__) return;
    window.__PHOTOCOM_SEARCH_SUGGEST__ = true;

    var wraps = document.querySelectorAll('[data-search-suggest]');
    if (!wraps.length) return;

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatPrice(value) {
        var n = Number(value) || 0;
        return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' MAD';
    }

    function buildAllHref(wrap, q) {
        var base = wrap.getAttribute('data-suggest-all-href') || '/catalogue.php';
        var param = wrap.getAttribute('data-suggest-all-param') || 'q';
        var catSlug = wrap.getAttribute('data-search-cat') || '';
        var params = [param + '=' + encodeURIComponent(q)];
        if (catSlug) {
            params.push('cat=' + encodeURIComponent(catSlug));
        }
        var join = base.indexOf('?') === -1 ? '?' : '&';
        return base + join + params.join('&');
    }

    function bindWrap(wrap) {
        var input = wrap.querySelector('[data-search-input]');
        var results = wrap.querySelector('[data-search-results]');
        if (!input || !results) return;

        var apiUrl = wrap.getAttribute('data-suggest-api') || '/api/search-suggest';
        var linkMode = wrap.getAttribute('data-suggest-link') || 'follow';
        var pickerKey = wrap.getAttribute('data-suggest-picker') || '';
        var timer = null;
        var controller = null;

        function hide() {
            results.hidden = true;
            results.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
        }

        function selectPickerItem(item) {
            input.value = item.name;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            if (!pickerKey) return;
            var picker = document.querySelector('[data-product-picker="' + pickerKey + '"]');
            if (!picker) return;
            var checkbox = picker.querySelector('input[type="checkbox"][value="' + item.id + '"]');
            if (!checkbox) return;
            checkbox.checked = true;
            var option = checkbox.closest('.he-product-option');
            if (option) {
                option.style.display = '';
                option.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }

        function render(items, q) {
            if (!items.length) {
                results.innerHTML = '<p class="header-search-suggest-empty">Aucun résultat pour « ' + escapeHtml(q) + ' »</p>';
                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
                return;
            }

            results.innerHTML = items.map(function (item) {
                var img = item.image
                    ? '<img src="' + escapeHtml(item.image) + '" alt="" width="48" height="48" loading="lazy">'
                    : '<span class="header-search-suggest-ph">PC</span>';
                var meta = item.brand ? escapeHtml(item.brand) : '';
                if (item.sku) {
                    meta += (meta ? ' · ' : '') + 'SKU ' + escapeHtml(item.sku);
                }
                return (
                    '<a class="header-search-suggest-item" role="option" href="' + escapeHtml(item.url) + '" data-suggest-item data-item-id="' + escapeHtml(item.id) + '">'
                    + '<span class="header-search-suggest-media">' + img + '</span>'
                    + '<span class="header-search-suggest-copy">'
                    + (meta ? '<span class="header-search-suggest-brand">' + meta + '</span>' : '')
                    + '<strong>' + escapeHtml(item.name) + '</strong>'
                    + '<span class="header-search-suggest-price">' + escapeHtml(formatPrice(item.price)) + '</span>'
                    + '</span>'
                    + '</a>'
                );
            }).join('')
            + '<a class="header-search-suggest-all" href="' + escapeHtml(buildAllHref(wrap, q)) + '">Voir tous les résultats</a>';

            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');

            results.querySelectorAll('[data-suggest-item]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    if (linkMode === 'follow') return;
                    e.preventDefault();
                    var itemId = link.getAttribute('data-item-id');
                    var item = items.find(function (row) { return String(row.id) === String(itemId); });
                    if (!item) return;
                    if (linkMode === 'picker') {
                        selectPickerItem(item);
                    } else if (linkMode === 'fill') {
                        input.value = item.name;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    hide();
                });
            });
        }

        function fetchSuggest(q) {
            if (controller) controller.abort();
            controller = new AbortController();
            fetch(apiUrl + '?q=' + encodeURIComponent(q) + '&limit=8', {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        hide();
                        return;
                    }
                    render(data.items || [], q);
                })
                .catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                    hide();
                });
        }

        input.addEventListener('input', function () {
            var q = String(input.value || '').trim();
            if (timer) clearTimeout(timer);
            if (q.length < 2) {
                hide();
                return;
            }
            timer = setTimeout(function () { fetchSuggest(q); }, 220);
        });

        input.addEventListener('focus', function () {
            var q = String(input.value || '').trim();
            if (q.length >= 2 && results.innerHTML) {
                results.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) hide();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hide();
        });
    }

    wraps.forEach(bindWrap);

    // Toggle recherche mobile
    var toggle = document.querySelector('[data-search-toggle]');
    var mobilePanel = document.querySelector('[data-mobile-search]');
    if (toggle && mobilePanel) {
        toggle.addEventListener('click', function () {
            var open = mobilePanel.hasAttribute('hidden');
            if (open) {
                mobilePanel.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                document.body.classList.add('mobile-search-open');
                var focusInput = mobilePanel.querySelector('[data-search-input]');
                if (focusInput) {
                    window.setTimeout(function () { focusInput.focus(); }, 50);
                }
            } else {
                mobilePanel.setAttribute('hidden', '');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('mobile-search-open');
            }
        });
    }
})();
