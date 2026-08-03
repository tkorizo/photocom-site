/**
 * Éditeur riche léger pour pages légales (paragraphe, titres, alignement, liens).
 */
(function () {
    var editor = document.querySelector('[data-rich-editor]');
    if (!editor) return;

    var area = editor.querySelector('[data-rich-area]');
    var input = editor.querySelector('[data-rich-input]');
    var toolbar = editor.querySelector('[data-rich-toolbar]');
    if (!area || !input || !toolbar) return;

    function sync() {
        input.value = area.innerHTML;
    }

    function focusArea() {
        area.focus();
    }

    function exec(cmd, value) {
        focusArea();
        document.execCommand(cmd, false, value || null);
        sync();
    }

    toolbar.querySelectorAll('[data-cmd]').forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
            e.preventDefault();
            var cmd = btn.getAttribute('data-cmd');
            if (cmd === 'createLink') {
                var url = window.prompt('URL du lien (https://… ou /page/…)', 'https://');
                if (!url) return;
                exec('createLink', url);
                return;
            }
            if (cmd === 'formatBlock') {
                exec('formatBlock', btn.getAttribute('data-value') || 'p');
                return;
            }
            exec(cmd);
        });
    });

    area.addEventListener('input', sync);
    area.addEventListener('blur', sync);
    var form = editor.closest('form');
    if (form) form.addEventListener('submit', sync);

    // Initialise depuis le textarea si l’éditeur est vide
    if (!area.innerHTML.trim() && input.value.trim()) {
        area.innerHTML = input.value;
    }
    sync();
})();
