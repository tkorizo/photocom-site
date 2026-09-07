/**
 * Panier PHOTOCOM — localStorage + sync serveur si client connecté.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'photocom_cart_v1';
    var cart = null;
    var syncTimer = null;
    var syncing = false;

    function config() {
        return window.PHOTOCOM || {};
    }

    function isLoggedIn() {
        return !!config().customerLoggedIn;
    }

    function load() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var parsed = raw ? JSON.parse(raw) : [];
            cart = Array.isArray(parsed) ? parsed.filter(isValidItem) : [];
        } catch (e) {
            cart = [];
        }
        return cart;
    }

    function isValidItem(item) {
        return item
            && typeof item === 'object'
            && Number(item.id) > 0
            && typeof item.name === 'string'
            && item.name.trim() !== ''
            && Number(item.price) >= 0
            && Number(item.qty) > 0;
    }

    function save(options) {
        options = options || {};
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        document.dispatchEvent(new CustomEvent('photocom:cart-changed', { detail: { items: cart.slice() } }));
        updateBadge();
        renderCartPage();
        renderAccountCart();
        if (!options.skipSync) {
            scheduleSync('replace');
        }
    }

    function getItems() {
        if (!cart) load();
        return cart.slice();
    }

    function count() {
        return getItems().reduce(function (sum, item) {
            return sum + Number(item.qty || 0);
        }, 0);
    }

    function total() {
        return getItems().reduce(function (sum, item) {
            return sum + Number(item.price || 0) * Number(item.qty || 0);
        }, 0);
    }

    function findIndex(id) {
        var nid = Number(id);
        for (var i = 0; i < cart.length; i++) {
            if (Number(cart[i].id) === nid) return i;
        }
        return -1;
    }

    function add(product, qty) {
        if (!cart) load();
        var id = Number(product.id);
        if (!(id > 0) || !product.name) return false;

        var amount = Math.max(1, parseInt(qty, 10) || 1);
        var idx = findIndex(id);
        if (idx >= 0) {
            cart[idx].qty = Number(cart[idx].qty) + amount;
            if (product.price != null) cart[idx].price = Number(product.price);
            if (product.image) cart[idx].image = product.image;
            if (product.slug) cart[idx].slug = product.slug;
        } else {
            cart.push({
                id: id,
                slug: String(product.slug || ''),
                name: String(product.name),
                price: Number(product.price || 0),
                image: product.image ? String(product.image) : '',
                brand: product.brand ? String(product.brand) : '',
                qty: amount,
            });
        }
        save();
        return true;
    }

    function setQty(id, qty) {
        if (!cart) load();
        var idx = findIndex(id);
        if (idx < 0) return;
        var next = parseInt(qty, 10);
        if (!(next > 0)) {
            cart.splice(idx, 1);
        } else {
            cart[idx].qty = next;
        }
        save();
    }

    function remove(id) {
        if (!cart) load();
        var idx = findIndex(id);
        if (idx < 0) return;
        cart.splice(idx, 1);
        save();
    }

    function clear() {
        cart = [];
        save();
    }

    function formatPrice(value) {
        var n = Number(value) || 0;
        return n.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' MAD TTC';
    }

    function updateBadge() {
        var n = count();
        document.querySelectorAll('[data-cart-badge]').forEach(function (el) {
            el.textContent = String(n);
            el.hidden = n < 1;
        });
        document.querySelectorAll('[data-cart-link]').forEach(function (el) {
            el.classList.toggle('has-items', n > 0);
            el.setAttribute('aria-label', n > 0 ? 'Panier (' + n + ' article' + (n > 1 ? 's' : '') + ')' : 'Panier');
        });
    }

    function scheduleSync(mode) {
        if (!isLoggedIn()) return;
        if (syncTimer) clearTimeout(syncTimer);
        syncTimer = setTimeout(function () {
            syncWithServer(mode || 'replace');
        }, 350);
    }

    function syncWithServer(mode) {
        if (!isLoggedIn() || syncing) return;
        syncing = true;
        if (!cart) load();

        fetch('/api/cart', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': (window.PHOTOCOM && window.PHOTOCOM.csrf) || '',
            },
            body: JSON.stringify({ mode: mode || 'replace', items: cart }),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.ok || !Array.isArray(data.items)) return;
                cart = data.items.filter(isValidItem);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
                updateBadge();
                renderCartPage();
                renderAccountCart();
            })
            .catch(function () { /* silencieux */ })
            .finally(function () { syncing = false; });
    }

    function pullAndMerge() {
        if (!isLoggedIn()) return;
        if (!cart) load();

        fetch('/api/cart', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        })
            .then(function (res) {
                if (res.status === 401) return null;
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.ok) return;
                var remote = Array.isArray(data.items) ? data.items.filter(isValidItem) : [];
                // Merge local + remote then push
                var map = {};
                remote.concat(cart).forEach(function (item) {
                    if (!isValidItem(item)) return;
                    var id = Number(item.id);
                    if (!map[id] || Number(item.qty) > Number(map[id].qty)) {
                        map[id] = item;
                    } else if (map[id] && Number(item.qty) === Number(map[id].qty)) {
                        map[id] = Object.assign({}, map[id], item);
                    }
                });
                cart = Object.keys(map).map(function (k) { return map[k]; });
                save({ skipSync: true });
                syncWithServer('replace');
            })
            .catch(function () { /* silencieux */ });
    }

    function productFromButton(btn) {
        return {
            id: btn.getAttribute('data-id'),
            slug: btn.getAttribute('data-slug') || '',
            name: btn.getAttribute('data-name') || '',
            price: btn.getAttribute('data-price') || '0',
            image: btn.getAttribute('data-image') || '',
            brand: btn.getAttribute('data-brand') || '',
        };
    }

    function markAdded(btn) {
        if (!btn) return;
        btn.classList.add('is-added');
        var prevLabel = btn.getAttribute('aria-label') || 'Ajouter au panier';
        btn.setAttribute('aria-label', 'Ajouté au panier');
        btn.setAttribute('title', 'Ajouté');
        window.setTimeout(function () {
            btn.classList.remove('is-added');
            btn.setAttribute('aria-label', prevLabel);
            btn.setAttribute('title', prevLabel);
        }, 1400);
    }

    function buildWhatsAppText() {
        var items = getItems();
        if (!items.length) return '';

        var lines = ['Bonjour, je souhaite passer commande :', ''];
        items.forEach(function (item, i) {
            var lineTotal = Number(item.price) * Number(item.qty);
            lines.push(
                (i + 1) + '. ' + item.name
                + ' × ' + item.qty
                + ' — ' + formatPrice(lineTotal)
            );
        });
        lines.push('');
        lines.push('Total : ' + formatPrice(total()));
        lines.push('');
        lines.push('Merci !');
        return lines.join('\n');
    }

    function whatsappUrl() {
        var digits = String(config().whatsapp || '').replace(/\D+/g, '');
        if (!digits) return '#';
        return 'https://wa.me/' + digits + '?text=' + encodeURIComponent(buildWhatsAppText());
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCartPage() {
        var root = document.querySelector('[data-cart-page]');
        if (!root) return;

        var items = getItems();
        var empty = root.querySelector('[data-cart-empty]');
        var content = root.querySelector('[data-cart-content]');
        var list = root.querySelector('[data-cart-list]');
        var totalEl = root.querySelector('[data-cart-total]');
        var countEl = root.querySelector('[data-cart-count-label]');
        var orderBtn = root.querySelector('[data-cart-order]');

        if (!items.length) {
            if (empty) empty.hidden = false;
            if (content) content.hidden = true;
            return;
        }

        if (empty) empty.hidden = true;
        if (content) content.hidden = false;

        if (countEl) {
            countEl.textContent = items.length + ' référence' + (items.length > 1 ? 's' : '')
                + ' · ' + count() + ' article' + (count() > 1 ? 's' : '');
        }

        if (list) {
            list.innerHTML = items.map(function (item) {
                var href = item.slug ? '/produit/' + encodeURIComponent(item.slug) : '/catalogue.php';
                var img = item.image
                    ? '<img src="' + escapeHtml(item.image) + '" alt="">'
                    : '<span class="product-placeholder">PC</span>';
                var line = Number(item.price) * Number(item.qty);
                return (
                    '<article class="cart-line" data-cart-line data-id="' + escapeHtml(item.id) + '">'
                    + '<a href="' + escapeHtml(href) + '" class="cart-line-media" target="_blank" rel="noopener">' + img + '</a>'
                    + '<div class="cart-line-body">'
                    + (item.brand ? '<p class="product-brand">' + escapeHtml(item.brand) + '</p>' : '')
                    + '<h2><a href="' + escapeHtml(href) + '" target="_blank" rel="noopener">' + escapeHtml(item.name) + '</a></h2>'
                    + '<p class="cart-line-unit">' + escapeHtml(formatPrice(item.price)) + ' / unité</p>'
                    + '<div class="cart-line-controls">'
                    + '<div class="cart-qty" role="group" aria-label="Quantité">'
                    + '<button type="button" class="cart-qty-btn" data-cart-qty-dec aria-label="Diminuer">−</button>'
                    + '<input type="number" class="cart-qty-input" data-cart-qty-input min="1" step="1" value="' + Number(item.qty) + '" aria-label="Quantité">'
                    + '<button type="button" class="cart-qty-btn" data-cart-qty-inc aria-label="Augmenter">+</button>'
                    + '</div>'
                    + '<button type="button" class="cart-remove" data-cart-remove>Retirer</button>'
                    + '</div>'
                    + '</div>'
                    + '<div class="cart-line-total">'
                    + '<strong>' + escapeHtml(formatPrice(line)) + '</strong>'
                    + '</div>'
                    + '</article>'
                );
            }).join('');
        }

        if (totalEl) totalEl.textContent = formatPrice(total());
        if (orderBtn) {
            orderBtn.href = whatsappUrl();
            orderBtn.setAttribute('aria-disabled', 'false');
        }
    }

    function renderAccountCart() {
        var root = document.querySelector('[data-account-cart]');
        if (!root) return;

        var items = getItems();
        var empty = root.querySelector('[data-account-cart-empty]');
        var list = root.querySelector('[data-account-cart-list]');
        var totalEl = root.querySelector('[data-account-cart-total]');
        var countEl = root.querySelector('[data-account-cart-count]');

        if (countEl) {
            countEl.textContent = count() + ' article' + (count() > 1 ? 's' : '');
        }
        if (totalEl) {
            totalEl.textContent = formatPrice(total());
        }

        if (!items.length) {
            if (empty) empty.hidden = false;
            if (list) {
                list.hidden = true;
                list.innerHTML = '';
            }
            return;
        }

        if (empty) empty.hidden = true;
        if (list) {
            list.hidden = false;
            list.innerHTML = items.slice(0, 5).map(function (item) {
                var href = item.slug ? '/produit/' + encodeURIComponent(item.slug) : '/panier';
                var img = item.image
                    ? '<img src="' + escapeHtml(item.image) + '" alt="">'
                    : '<span class="product-placeholder">PC</span>';
                return (
                    '<a class="account-cart-line" href="' + escapeHtml(href) + '">'
                    + '<span class="account-cart-media">' + img + '</span>'
                    + '<span class="account-cart-copy">'
                    + '<strong>' + escapeHtml(item.name) + '</strong>'
                    + '<span>× ' + Number(item.qty) + ' · ' + escapeHtml(formatPrice(Number(item.price) * Number(item.qty))) + '</span>'
                    + '</span>'
                    + '</a>'
                );
            }).join('');
        }
    }

    function bindUi() {
        document.addEventListener('click', function (e) {
            var addBtn = e.target.closest('[data-cart-add]');
            if (addBtn) {
                e.preventDefault();
                if (add(productFromButton(addBtn), addBtn.getAttribute('data-qty') || 1)) {
                    markAdded(addBtn);
                }
                return;
            }

            var line = e.target.closest('[data-cart-line]');
            if (!line) return;
            var id = line.getAttribute('data-id');

            if (e.target.closest('[data-cart-remove]')) {
                remove(id);
                return;
            }
            if (e.target.closest('[data-cart-qty-dec]')) {
                var currentDec = Number((line.querySelector('[data-cart-qty-input]') || {}).value || 1);
                setQty(id, currentDec - 1);
                return;
            }
            if (e.target.closest('[data-cart-qty-inc]')) {
                var currentInc = Number((line.querySelector('[data-cart-qty-input]') || {}).value || 1);
                setQty(id, currentInc + 1);
            }
        });

        document.addEventListener('change', function (e) {
            var input = e.target.closest('[data-cart-qty-input]');
            if (!input) return;
            var line = input.closest('[data-cart-line]');
            if (!line) return;
            setQty(line.getAttribute('data-id'), input.value);
        });

        var clearBtn = document.querySelector('[data-cart-clear]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (window.confirm('Vider entièrement le panier ?')) clear();
            });
        }
    }

    window.PhotocomCart = {
        getItems: getItems,
        add: add,
        setQty: setQty,
        remove: remove,
        clear: clear,
        count: count,
        total: total,
        formatPrice: formatPrice,
        whatsappUrl: whatsappUrl,
        sync: function () { pullAndMerge(); },
    };

    load();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            updateBadge();
            renderCartPage();
            renderAccountCart();
            bindUi();
            pullAndMerge();
        });
    } else {
        updateBadge();
        renderCartPage();
        renderAccountCart();
        bindUi();
        pullAndMerge();
    }
})();
