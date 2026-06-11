(function () {
    var widget = document.querySelector('[data-chat-widget]');
    if (!widget) return;

    var toggle = widget.querySelector('[data-chat-toggle]');
    var panel = widget.querySelector('[data-chat-panel]');
    var closeBtn = widget.querySelector('[data-chat-close]');
    var form = widget.querySelector('[data-chat-form]');
    var messages = widget.querySelector('[data-chat-messages]');
    var config = window.PHOTOCOM_CHAT || {};
    var greeted = false;

    function addMessage(text, type) {
        if (!messages) return;
        var bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble--' + type;
        bubble.textContent = text;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    function openChat() {
        if (!panel || !toggle) return;
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        toggle.hidden = true;
        toggle.setAttribute('aria-expanded', 'true');
        widget.classList.add('is-open');
        if (!greeted && config.welcome) {
            addMessage(config.welcome, 'bot');
            greeted = true;
        }
        var input = form && form.querySelector('input[name="message"]');
        if (input) {
            window.setTimeout(function () { input.focus(); }, 80);
        }
    }

    function closeChat() {
        if (!panel || !toggle) return;
        panel.hidden = true;
        panel.setAttribute('aria-hidden', 'true');
        toggle.hidden = false;
        toggle.setAttribute('aria-expanded', 'false');
        widget.classList.remove('is-open');
    }

    if (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openChat();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeChat();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && widget.classList.contains('is-open')) {
            closeChat();
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = form.querySelector('input[name="message"]');
            var text = (input && input.value || '').trim();
            if (!text) return;

            addMessage(text, 'user');
            input.value = '';

            fetch('/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: text }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    addMessage(data.reply || config.offline || 'Merci, nous revenons vers vous rapidement.', 'bot');
                })
                .catch(function () {
                    addMessage(config.offline || 'Service temporairement indisponible.', 'bot');
                });
        });
    }
})();
