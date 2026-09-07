(function () {
    var root = document.querySelector('[data-home-builder]');
    if (!root) return;

    var canvas = document.getElementById('he-canvas');
    var headerOrderInput = document.getElementById('header_order');

    var meta = {
        promo: { title: 'Bandeau promo', desc: 'Texte affiché tout en haut du site' },
        nav: { title: 'Navigation', desc: 'Logo, favicon, ordre et disposition du header' },
        menu: { title: 'Menu catégories', desc: 'Ajoutez, renommez et liez librement les boutons du mega menu' },
        hero: { title: 'Hero', desc: 'Vidéo + image de secours, desktop et mobile' },
        featured: { title: 'Produits en vedette', desc: 'Choisissez précisément les produits affichés' },
        conseils: { title: 'Conseils / Vidéos', desc: 'Titres et 2 vidéos YouTube' },
        brands: { title: 'Marques', desc: 'Titres et logos cliquables vers chaque marque' },
        bestsellers: { title: 'Meilleures ventes', desc: 'Choix manuel ou sélection automatique' },
        trust: { title: 'Bandeau confiance', desc: '4 blocs d’arguments' },
        newsletter: { title: 'Newsletter', desc: 'Textes du formulaire d’inscription' },
        social: { title: 'Réseaux sociaux', desc: 'Liens affichés dans le header et le footer' }
    };

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

    var overlayInput = root.querySelector('[data-bind-overlay]');
    if (overlayInput) {
        overlayInput.addEventListener('input', function () {
            var hero = root.querySelector('.he-hero');
            if (hero) hero.style.setProperty('--he-overlay', overlayInput.value + '%');
        });
    }

    function syncCanvasAttrs() {
        if (!canvas) return;
        var align = root.querySelector('input[name="hero_align"]:checked');
        var vertical = root.querySelector('input[name="hero_vertical"]:checked');
        var height = root.querySelector('input[name="hero_height"]:checked');
        if (align) canvas.setAttribute('data-hero-align', align.value);
        if (vertical) canvas.setAttribute('data-hero-vertical', vertical.value);
        if (height) canvas.setAttribute('data-hero-height', height.value);
    }
    root.querySelectorAll('input[name="hero_align"], input[name="hero_vertical"], input[name="hero_height"]').forEach(function (input) {
        input.addEventListener('change', syncCanvasAttrs);
    });

    function syncSectionAlign(name) {
        var checked = root.querySelector('input[name="' + name + '"]:checked');
        var blockName = name.replace('_align', '');
        var block = root.querySelector('.he-block[data-block="' + blockName + '"]');
        if (checked && block) block.setAttribute('data-align', checked.value);
    }
    ['featured_align', 'conseils_align', 'brands_align', 'bestsellers_align', 'newsletter_align'].forEach(function (name) {
        root.querySelectorAll('input[name="' + name + '"]').forEach(function (input) {
            input.addEventListener('change', function () { syncSectionAlign(name); });
        });
    });

    root.querySelectorAll('input[name="nav_links_align"], input[name="mega_nav_align"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var nav = root.querySelector('.he-nav');
            if (!nav) return;
            var links = root.querySelector('input[name="nav_links_align"]:checked');
            var mega = root.querySelector('input[name="mega_nav_align"]:checked');
            if (links) nav.setAttribute('data-nav-align', links.value);
            if (mega) nav.setAttribute('data-mega-align', mega.value);
        });
    });

    function syncVisibility(checkboxName, blockName, extra) {
        var checkbox = root.querySelector('input[name="' + checkboxName + '"]');
        if (!checkbox) return;
        checkbox.addEventListener('change', function () {
            if (blockName) {
                var block = root.querySelector('.he-block[data-block="' + blockName + '"]');
                if (block) block.classList.toggle('is-hidden', !checkbox.checked);
            }
            if (typeof extra === 'function') extra(checkbox.checked);
        });
    }
    syncVisibility('promo_enabled', 'promo');
    syncVisibility('hero_enabled', 'hero');
    syncVisibility('featured_enabled', 'featured');
    syncVisibility('conseils_enabled', 'conseils');
    syncVisibility('brands_enabled', 'brands');
    syncVisibility('bestsellers_enabled', 'bestsellers');
    syncVisibility('trust_enabled', 'trust');
    syncVisibility('newsletter_enabled', 'newsletter');
    syncVisibility('search_visible', null, function (on) {
        var el = root.querySelector('.he-nav-search');
        if (el) el.classList.toggle('is-off', !on);
    });
    syncVisibility('hero_show_subtitle', null, function (on) {
        var el = root.querySelector('[data-preview="hero_subtitle"]');
        if (el) el.classList.toggle('is-off', !on);
    });

    // Header order drag & drop
    var orderList = document.getElementById('header-order-list');
    var dragEl = null;
    if (orderList) {
        orderList.querySelectorAll('.he-order-item').forEach(function (item) {
            item.addEventListener('dragstart', function () {
                dragEl = item;
                item.classList.add('is-dragging');
            });
            item.addEventListener('dragend', function () {
                item.classList.remove('is-dragging');
                dragEl = null;
                commitHeaderOrder();
            });
            item.addEventListener('dragover', function (e) {
                e.preventDefault();
                var target = item;
                if (!dragEl || dragEl === target) return;
                var rect = target.getBoundingClientRect();
                var before = (e.clientY - rect.top) < rect.height / 2;
                orderList.insertBefore(dragEl, before ? target : target.nextSibling);
            });
        });
    }

    function commitHeaderOrder() {
        if (!orderList || !headerOrderInput) return;
        var parts = [];
        orderList.querySelectorAll('.he-order-item').forEach(function (item) {
            parts.push(item.getAttribute('data-part'));
        });
        headerOrderInput.value = parts.join(',');
        var preview = document.getElementById('he-nav-order-preview');
        if (!preview) return;
        parts.forEach(function (part) {
            var el = preview.querySelector('[data-nav-part="' + part + '"]');
            if (el) preview.appendChild(el);
        });
    }

    // Menu catégories drag & preview
    var menuList = document.getElementById('menu-category-list');
    var menuTemplate = document.getElementById('menu-item-template');
    var menuAddBtn = document.getElementById('menu-item-add');
    var menuDragEl = null;

    function categoryLabelFromSelect(select) {
        if (!select) return '';
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return '';
        return (opt.textContent || '').replace(/^—+\s*/, '').trim();
    }

    function refreshMenuPreview() {
        if (!menuList) return;
        var chips = document.getElementById('he-menu-cats-preview');
        var mega = document.getElementById('he-menu-preview');
        var names = [];
        if (chips) chips.innerHTML = '';
        menuList.querySelectorAll('[data-menu-item]').forEach(function (item) {
            var labelInput = item.querySelector('[data-menu-label]');
            var catSelect = item.querySelector('[data-menu-category]');
            var check = item.querySelector('[data-menu-visible]');
            var label = labelInput ? labelInput.value.trim() : '';
            if (!label) label = categoryLabelFromSelect(catSelect);
            var visible = check ? check.checked : true;
            if (chips) {
                var chip = document.createElement('span');
                chip.className = 'he-menu-chip' + (visible ? '' : ' is-off');
                chip.textContent = label || 'Sans titre';
                chips.appendChild(chip);
            }
            if (visible && label) names.push(label);
        });
        if (mega) {
            mega.textContent = names.length ? names.join(' · ') : 'Aucun élément visible';
        }
    }

    function bindMenuItem(item) {
        item.addEventListener('dragstart', function () {
            menuDragEl = item;
            item.classList.add('is-dragging');
        });
        item.addEventListener('dragend', function () {
            item.classList.remove('is-dragging');
            menuDragEl = null;
            refreshMenuPreview();
        });
        item.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!menuDragEl || menuDragEl === item) return;
            var rect = item.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            menuList.insertBefore(menuDragEl, before ? item : item.nextSibling);
        });
        var labelInput = item.querySelector('[data-menu-label]');
        var catSelect = item.querySelector('[data-menu-category]');
        var check = item.querySelector('[data-menu-visible]');
        var removeBtn = item.querySelector('[data-menu-remove]');
        if (labelInput) labelInput.addEventListener('input', refreshMenuPreview);
        if (catSelect) {
            catSelect.addEventListener('change', function () {
                if (labelInput && !labelInput.value.trim()) {
                    labelInput.placeholder = categoryLabelFromSelect(catSelect) || 'Texte du bouton';
                }
                refreshMenuPreview();
            });
        }
        if (check) check.addEventListener('change', refreshMenuPreview);
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                item.remove();
                refreshMenuPreview();
            });
        }
    }

    if (menuList) {
        menuList.querySelectorAll('[data-menu-item]').forEach(bindMenuItem);
    }

    if (menuAddBtn && menuTemplate && menuList) {
        menuAddBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var id = 'item-' + Math.random().toString(36).slice(2, 10);
            var html = menuTemplate.innerHTML.replace(/__ID__/g, id);
            var wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            var item = wrap.firstElementChild;
            if (!item) return;
            menuList.appendChild(item);
            bindMenuItem(item);
            var labelInput = item.querySelector('[data-menu-label]');
            if (labelInput) labelInput.focus();
            refreshMenuPreview();
        });
    }

    function refreshHeroBadge() {
        var badge = root.querySelector('[data-preview-video]');
        if (!badge) return;
        var hasVideo = ['hero_video', 'hero_video_mobile'].some(function (field) {
            var del = document.getElementById(field + '_delete');
            var current = root.querySelector('[name="' + field + '_current"]');
            var file = root.querySelector('[name="' + field + '_file"]');
            if (del && del.value === '1') return false;
            if (file && file.files && file.files[0]) return true;
            return !!(current && current.value);
        });
        var hasImage = ['hero_image', 'hero_image_mobile'].some(function (field) {
            var del = document.getElementById(field + '_delete');
            var current = root.querySelector('[name="' + field + '_current"]');
            var file = root.querySelector('[name="' + field + '_file"]');
            if (del && del.value === '1') return false;
            if (file && file.files && file.files[0]) return true;
            return !!(current && current.value);
        });
        badge.textContent = hasVideo && hasImage
            ? 'Vidéo + image de secours'
            : (hasVideo ? 'Vidéo arrière-plan' : (hasImage ? 'Image de secours' : 'Fond sombre — ajoutez image et vidéo'));
    }

    root.querySelectorAll('[data-clear-media]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var kind = btn.getAttribute('data-clear-media');
            var del = document.getElementById(kind + '_delete');
            if (del) del.value = '1';
            var file = root.querySelector('[name="' + kind + '_file"]');
            if (file) file.value = '';
            var current = root.querySelector('[name="' + kind + '_current"]');
            if (current) current.value = '';
            var wrap = root.querySelector('[data-preview-wrap="' + kind + '"]');
            if (wrap) wrap.hidden = true;
            var note = root.querySelector('[data-current-file="' + kind + '"]');
            if (note) note.hidden = true;
            btn.hidden = true;
            refreshHeroBadge();
        });
    });

    root.querySelectorAll('[data-hero-image-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var field = (input.getAttribute('name') || '').replace(/_file$/, '');
            var del = document.getElementById(field + '_delete');
            if (del) del.value = '0';
            var url = URL.createObjectURL(file);
            var hero = root.querySelector('.he-hero');
            if (hero) {
                hero.style.backgroundImage = 'url(' + url + ')';
                hero.style.backgroundSize = 'cover';
                hero.style.backgroundPosition = 'center';
            }
            refreshHeroBadge();
        });
    });

    root.querySelectorAll('input[type="file"][name$="_file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var field = (input.getAttribute('name') || '').replace(/_file$/, '');
            var del = document.getElementById(field + '_delete');
            if (del && input.files && input.files[0]) del.value = '0';
            refreshHeroBadge();
        });
    });

    // Logo / favicon preview
    var defaultLogo = '/assets/images/logo-photocom.png';
    var defaultLogoMark = '/assets/images/logo-admin.webp';
    root.querySelectorAll('[data-brand-file]').forEach(function (input) {
        input.addEventListener('change', function () {
            var kind = input.getAttribute('data-brand-file');
            var file = input.files && input.files[0];
            if (!file) return;
            var del = document.getElementById('site_' + kind + '_delete');
            if (del) del.value = '0';
            var url = URL.createObjectURL(file);
            var preview = document.getElementById('he-brand-' + kind + '-preview');
            if (preview) {
                preview.src = url;
                preview.hidden = false;
            }
            if (kind === 'logo') {
                var navLogo = document.getElementById('he-nav-logo-img');
                if (navLogo) navLogo.src = url;
            }
            if (kind === 'favicon') {
                var empty = document.getElementById('he-brand-favicon-empty');
                if (empty) empty.hidden = true;
            }
        });
    });

    root.querySelectorAll('[data-clear-brand]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var kind = btn.getAttribute('data-clear-brand');
            var del = document.getElementById('site_' + kind + '_delete');
            if (del) del.value = '1';
            var fileInput = root.querySelector('[data-brand-file="' + kind + '"]');
            if (fileInput) fileInput.value = '';
            if (kind === 'logo') {
                var preview = document.getElementById('he-brand-logo-preview');
                var navLogo = document.getElementById('he-nav-logo-img');
                if (preview) preview.src = defaultLogo;
                if (navLogo) navLogo.src = defaultLogo;
            } else if (kind === 'logo_mark') {
                var markPreview = document.getElementById('he-brand-logo_mark-preview');
                if (markPreview) markPreview.src = defaultLogoMark;
            } else {
                var favPreview = document.getElementById('he-brand-favicon-preview');
                var empty = document.getElementById('he-brand-favicon-empty');
                if (favPreview) {
                    favPreview.removeAttribute('src');
                    favPreview.hidden = true;
                }
                if (empty) empty.hidden = false;
            }
        });
    });

    // Product search filter
    root.querySelectorAll('[data-product-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            var key = input.getAttribute('data-product-search');
            var q = input.value.trim().toLowerCase();
            var picker = root.querySelector('[data-product-picker="' + key + '"]');
            if (!picker) return;
            picker.querySelectorAll('.he-product-option').forEach(function (opt) {
                var text = (opt.textContent || '').toLowerCase();
                opt.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    });

    selectBlock('promo');
})();
