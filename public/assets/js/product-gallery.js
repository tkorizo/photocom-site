(function () {
    var root = document.querySelector('[data-product-gallery]');
    if (!root) return;

    var images = [];
    try {
        images = JSON.parse(root.getAttribute('data-gallery-images') || '[]');
    } catch (e) {
        images = [];
    }
    if (!Array.isArray(images) || images.length === 0) return;

    var alt = root.getAttribute('data-gallery-alt') || '';
    var main = root.querySelector('[data-gallery-main]');
    var openBtn = root.querySelector('[data-gallery-open]');
    var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-thumb]'));
    var index = 0;
    var scale = 1;
    var minScale = 1;
    var maxScale = 4;
    var panX = 0;
    var panY = 0;
    var dragging = false;
    var lastX = 0;
    var lastY = 0;
    var pinchStart = 0;
    var pinchScale = 1;
    var lastFocus = null;
    var supportsDialog = typeof HTMLDialogElement === 'function';

    root.classList.add('has-lightbox');
    if (main && main.closest('.product-studio-stage')) {
        main.closest('.product-studio-stage').classList.add('has-lightbox');
    }

    function icon(path) {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' + path + '</svg>';
    }

    var lightbox = document.createElement(supportsDialog ? 'dialog' : 'div');
    lightbox.className = 'product-lightbox';
    lightbox.setAttribute('aria-label', 'Aperçu de l’image');
    if (!supportsDialog) {
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
    }
    lightbox.innerHTML =
        '<div class="product-lightbox-inner">' +
            '<div class="product-lightbox-toolbar">' +
                '<span class="product-lightbox-counter" data-lb-counter></span>' +
                '<div class="product-lightbox-tools">' +
                    '<button type="button" class="product-lightbox-btn" data-lb-zoom-out aria-label="Zoom arrière">' +
                        icon('<circle cx="11" cy="11" r="6.5"/><path d="M16.5 16.5L21 21" stroke-linecap="round"/><path d="M8.5 11h5" stroke-linecap="round"/>') +
                    '</button>' +
                    '<button type="button" class="product-lightbox-btn" data-lb-zoom-in aria-label="Zoom avant">' +
                        icon('<circle cx="11" cy="11" r="6.5"/><path d="M16.5 16.5L21 21" stroke-linecap="round"/><path d="M11 8.5v5M8.5 11h5" stroke-linecap="round"/>') +
                    '</button>' +
                    '<button type="button" class="product-lightbox-btn" data-lb-zoom-reset aria-label="Taille réelle">100%</button>' +
                    '<button type="button" class="product-lightbox-btn" data-lb-close aria-label="Fermer">' +
                        icon('<path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>') +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="product-lightbox-stage" data-lb-stage>' +
                '<button type="button" class="product-lightbox-btn product-lightbox-nav is-prev" data-lb-prev aria-label="Image précédente">' +
                    icon('<path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/>') +
                '</button>' +
                '<img class="product-lightbox-image" data-lb-image alt="">' +
                '<button type="button" class="product-lightbox-btn product-lightbox-nav is-next" data-lb-next aria-label="Image suivante">' +
                    icon('<path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>') +
                '</button>' +
            '</div>' +
            '<p class="product-lightbox-hint">Molette ou boutons pour zoomer · glisser pour déplacer · flèches du clavier pour changer d’image</p>' +
        '</div>';
    document.body.appendChild(lightbox);

    var lbImage = lightbox.querySelector('[data-lb-image]');
    var lbStage = lightbox.querySelector('[data-lb-stage]');
    var lbCounter = lightbox.querySelector('[data-lb-counter]');
    var lbPrev = lightbox.querySelector('[data-lb-prev]');
    var lbNext = lightbox.querySelector('[data-lb-next]');
    var lbZoomIn = lightbox.querySelector('[data-lb-zoom-in]');
    var lbZoomOut = lightbox.querySelector('[data-lb-zoom-out]');
    var lbZoomReset = lightbox.querySelector('[data-lb-zoom-reset]');
    var lbClose = lightbox.querySelector('[data-lb-close]');
    var many = images.length > 1;

    lbPrev.hidden = !many;
    lbNext.hidden = !many;

    function applyThumbs() {
        thumbs.forEach(function (thumb, i) {
            var on = i === index;
            thumb.classList.toggle('is-active', on);
            thumb.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function isOpen() {
        return supportsDialog ? !!lightbox.open : lightbox.classList.contains('is-open');
    }

    function show(i) {
        index = ((i % images.length) + images.length) % images.length;
        var src = images[index];
        if (main) main.src = src;
        applyThumbs();
        if (isOpen()) {
            resetZoom();
            lbImage.src = src;
            lbImage.alt = alt;
            lbCounter.textContent = (index + 1) + ' / ' + images.length;
        }
    }

    function resetZoom() {
        scale = 1;
        panX = 0;
        panY = 0;
        applyTransform();
    }

    function applyTransform() {
        lbImage.style.transform = 'translate(calc(-50% + ' + panX + 'px), calc(-50% + ' + panY + 'px)) scale(' + scale + ')';
        lbStage.classList.toggle('is-zoomed', scale > 1.01);
        lbZoomOut.disabled = scale <= minScale + 0.01;
        lbZoomIn.disabled = scale >= maxScale - 0.01;
        lbZoomReset.textContent = Math.round(scale * 100) + '%';
    }

    function setScale(next, cx, cy) {
        var prev = scale;
        scale = Math.min(maxScale, Math.max(minScale, next));
        if (scale === 1) {
            panX = 0;
            panY = 0;
        } else if (typeof cx === 'number' && typeof cy === 'number' && prev > 0) {
            var rect = lbStage.getBoundingClientRect();
            var originX = cx - rect.left - rect.width / 2;
            var originY = cy - rect.top - rect.height / 2;
            var ratio = scale / prev;
            panX = originX - (originX - panX) * ratio;
            panY = originY - (originY - panY) * ratio;
        }
        applyTransform();
    }

    function onClosed() {
        lightbox.classList.remove('is-open');
        document.documentElement.classList.remove('lightbox-open');
        document.body.style.overflow = '';
        dragging = false;
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function openAt(i) {
        lastFocus = document.activeElement;
        show(i);
        lbImage.src = images[index];
        lbImage.alt = alt;
        lbCounter.textContent = (index + 1) + ' / ' + images.length;
        resetZoom();
        document.documentElement.classList.add('lightbox-open');
        document.body.style.overflow = 'hidden';
        if (supportsDialog) {
            if (!lightbox.open) lightbox.showModal();
        } else {
            lightbox.classList.add('is-open');
        }
        lbClose.focus();
    }

    function close() {
        if (supportsDialog && lightbox.open) {
            lightbox.close();
            return;
        }
        onClosed();
    }

    lightbox.addEventListener('close', onClosed);

    thumbs.forEach(function (thumb, i) {
        thumb.addEventListener('click', function () {
            show(i);
        });
    });

    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openAt(index);
        });
    }

    if (main) {
        main.addEventListener('click', function () {
            openAt(index);
        });
    }

    lbClose.addEventListener('click', close);
    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) close();
    });
    lbPrev.addEventListener('click', function (e) {
        e.stopPropagation();
        show(index - 1);
    });
    lbNext.addEventListener('click', function (e) {
        e.stopPropagation();
        show(index + 1);
    });
    lbZoomIn.addEventListener('click', function (e) {
        e.stopPropagation();
        setScale(scale + 0.5);
    });
    lbZoomOut.addEventListener('click', function (e) {
        e.stopPropagation();
        setScale(scale - 0.5);
    });
    lbZoomReset.addEventListener('click', function (e) {
        e.stopPropagation();
        if (scale > 1) resetZoom();
        else setScale(2);
    });

    lbStage.addEventListener('dblclick', function (e) {
        e.preventDefault();
        if (scale > 1) resetZoom();
        else setScale(2.5, e.clientX, e.clientY);
    });

    lbStage.addEventListener('wheel', function (e) {
        if (!isOpen()) return;
        e.preventDefault();
        var delta = e.deltaY > 0 ? -0.2 : 0.2;
        setScale(scale + delta, e.clientX, e.clientY);
    }, { passive: false });

    lbStage.addEventListener('pointerdown', function (e) {
        if (e.target.closest('button')) return;
        if (scale <= 1.01) return;
        dragging = true;
        lastX = e.clientX;
        lastY = e.clientY;
        lbStage.classList.add('is-panning');
        lbStage.setPointerCapture(e.pointerId);
    });

    lbStage.addEventListener('pointermove', function (e) {
        if (!dragging) return;
        panX += e.clientX - lastX;
        panY += e.clientY - lastY;
        lastX = e.clientX;
        lastY = e.clientY;
        applyTransform();
    });

    function endPan(e) {
        if (!dragging) return;
        dragging = false;
        lbStage.classList.remove('is-panning');
        if (e && lbStage.hasPointerCapture(e.pointerId)) {
            lbStage.releasePointerCapture(e.pointerId);
        }
    }

    lbStage.addEventListener('pointerup', endPan);
    lbStage.addEventListener('pointercancel', endPan);

    lbStage.addEventListener('touchstart', function (e) {
        if (e.touches.length === 2) {
            dragging = false;
            var a = e.touches[0];
            var b = e.touches[1];
            pinchStart = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
            pinchScale = scale;
        }
    }, { passive: true });

    lbStage.addEventListener('touchmove', function (e) {
        if (e.touches.length !== 2 || !pinchStart) return;
        e.preventDefault();
        var a = e.touches[0];
        var b = e.touches[1];
        var dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
        var midX = (a.clientX + b.clientX) / 2;
        var midY = (a.clientY + b.clientY) / 2;
        setScale(pinchScale * (dist / pinchStart), midX, midY);
    }, { passive: false });

    document.addEventListener('keydown', function (e) {
        if (!isOpen()) return;
        if (e.key === 'ArrowLeft' && many) {
            e.preventDefault();
            show(index - 1);
        } else if (e.key === 'ArrowRight' && many) {
            e.preventDefault();
            show(index + 1);
        } else if (e.key === '+' || e.key === '=') {
            e.preventDefault();
            setScale(scale + 0.5);
        } else if (e.key === '-' || e.key === '_') {
            e.preventDefault();
            setScale(scale - 0.5);
        } else if (!supportsDialog && e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });
})();
