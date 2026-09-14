(function () {
    var widget = document.querySelector('[data-chat-widget]');
    if (!widget) return;

    var toggle = widget.querySelector('[data-chat-toggle]');
    var panel = widget.querySelector('[data-chat-panel]');
    var closeBtn = widget.querySelector('[data-chat-close]');
    var form = widget.querySelector('[data-chat-form]');
    var messages = widget.querySelector('[data-chat-messages]');
    var config = window.PHOTOCOM_CHAT || {};
    var STORAGE_KEY = 'photocom_chat_state';
    var HEARTBEAT_KEY = 'photocom_chat_hb';
    var OLD_SID_KEY = 'photocom_chat_sid';
    var HEARTBEAT_MAX_MS = 25000;
    var history = [];
    var sessionId = '';
    var greeted = false;
    var blocked = false;

    function jsonParse(raw, fallback) {
        try {
            return JSON.parse(raw);
        } catch (err) {
            return fallback;
        }
    }

    function isHeartbeatFresh() {
        try {
            var stamp = parseInt(window.localStorage.getItem(HEARTBEAT_KEY) || '0', 10);
            return stamp > 0 && (Date.now() - stamp) < HEARTBEAT_MAX_MS;
        } catch (err) {
            return false;
        }
    }

    function beat() {
        try {
            window.localStorage.setItem(HEARTBEAT_KEY, String(Date.now()));
        } catch (err) {}
    }

    function persist() {
        var payload = JSON.stringify({
            messages: history.slice(-40),
            open: widget.classList.contains('is-open'),
            sid: sessionId,
            greeted: greeted,
            blocked: blocked,
        });
        try {
            window.sessionStorage.setItem(STORAGE_KEY, payload);
            window.localStorage.setItem(STORAGE_KEY, payload);
        } catch (err) {}
        beat();
    }

    function emptyState() {
        return { messages: [], open: false, sid: '', greeted: false, blocked: false };
    }

    function loadState() {
        var fallback = emptyState();
        try {
            var fromSession = window.sessionStorage.getItem(STORAGE_KEY);
            if (fromSession) {
                return Object.assign(fallback, jsonParse(fromSession, fallback));
            }
            if (isHeartbeatFresh()) {
                var fromLocal = window.localStorage.getItem(STORAGE_KEY);
                if (fromLocal) {
                    window.sessionStorage.setItem(STORAGE_KEY, fromLocal);
                    return Object.assign(fallback, jsonParse(fromLocal, fallback));
                }
            } else {
                window.localStorage.removeItem(STORAGE_KEY);
                window.localStorage.removeItem(OLD_SID_KEY);
            }
        } catch (err) {}
        return fallback;
    }

    function hideWidget() {
        blocked = true;
        persist();
        widget.hidden = true;
        widget.style.display = 'none';
        widget.setAttribute('aria-hidden', 'true');
        if (panel) {
            panel.hidden = true;
        }
        if (toggle) {
            toggle.hidden = true;
        }
    }

    function getSessionId() {
        if (sessionId) return sessionId;
        sessionId = 'pc' + Math.random().toString(36).slice(2) + Date.now().toString(36);
        persist();
        return sessionId;
    }

    function visitorHints() {
        var hints = {};
        try {
            hints.tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch (err) {}
        try {
            hints.lang = navigator.language || '';
        } catch (err) {}
        try {
            var ua = navigator.userAgent || '';
            var uaData = navigator.userAgentData || null;
            if (/iPad|Tablet|PlayBook/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) {
                hints.device = 'tablet';
            } else if ((uaData && uaData.mobile) || /Mobi|Android|iPhone|iPod|IEMobile|Opera Mini|webOS|BlackBerry/i.test(ua)) {
                hints.device = 'mobile';
            } else {
                hints.device = 'desktop';
            }
        } catch (err) {}
        return hints;
    }

    function addMessage(text, type, skipPersist) {
        if (!messages) return;
        var bubble = document.createElement('div');
        bubble.className = 'chat-bubble chat-bubble--' + type;
        bubble.setAttribute('dir', 'auto');
        if (type === 'bot') {
            bubble.innerHTML = formatBotHtml(text);
        } else {
            bubble.textContent = text;
        }
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        if (!skipPersist) {
            history.push({ text: text, type: type });
            persist();
        }
    }

    function renderHistory() {
        if (!messages) return;
        messages.innerHTML = '';
        history.forEach(function (item) {
            addMessage(item.text, item.type, true);
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function linkTag(href, label) {
        var safe = String(href || '');
        var isProduct = /^\/produit\/[a-z0-9-]+\/?$/i.test(safe);
        var isAccount = /^\/(compte|mon-compte)(\?tab=(login|register))?\/?$/i.test(safe);
        var isHttp = /^https?:\/\//i.test(safe);
        if (!isProduct && !isAccount && !isHttp) {
            return escapeHtml(label || href);
        }
        if (isHttp && !/^https?:\/\/(www\.)?photocom\.ma(\/|$)/i.test(safe) && !/^https?:\/\/wa\.me\//i.test(safe)) {
            return escapeHtml(label || href);
        }
        var attrs = 'href="' + escapeHtml(safe) + '"';
        if (!isProduct && !isAccount && !/photocom\.ma/i.test(safe)) {
            attrs += ' target="_blank" rel="noopener noreferrer"';
        }
        return '<a class="chat-link" ' + attrs + '>' + escapeHtml(label || href) + '</a>';
    }

    function formatBotHtml(text) {
        var raw = String(text || '');
        var tokens = [];
        function stash(href, label) {
            var index = tokens.length;
            tokens.push(linkTag(href, label));
            return '\u0000' + index + '\u0001';
        }
        raw = raw.replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+|\/produit\/[a-z0-9-]+|\/compte(?:\?tab=(?:login|register))?|\/mon-compte)\)/gi, function (_, label, href) {
            return stash(href, label);
        });
        raw = raw.replace(/(https?:\/\/[^\s]+)/g, function (url) {
            var clean = url.replace(/[),.;!?]+$/g, '');
            var punct = url.slice(clean.length);
            return stash(clean, clean) + punct;
        });
        raw = raw.replace(/(^|[\s])(\/produit\/[a-z0-9-]+\/?)/gi, function (_, prefix, path) {
            return prefix + stash(path, path);
        });
        raw = raw.replace(/(^|[\s])(\/(?:compte(?:\?tab=(?:login|register))?|mon-compte)\/?)/gi, function (_, prefix, path) {
            return prefix + stash(path, path);
        });
        var escaped = escapeHtml(raw);
        escaped = escaped.replace(/\u0000(\d+)\u0001/g, function (_, index) {
            return tokens[Number(index)] || '';
        });
        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        return escaped.replace(/\n/g, '<br>');
    }

    function openChat(fromRestore) {
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
        if (!fromRestore) {
            persist();
        }
        var input = form && form.querySelector('input[name="message"]');
        if (input && !fromRestore) {
            window.setTimeout(function () { input.focus(); }, 80);
        }
        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    }

    function closeChat() {
        if (!panel || !toggle) return;
        panel.hidden = true;
        panel.setAttribute('aria-hidden', 'true');
        toggle.hidden = false;
        toggle.setAttribute('aria-expanded', 'false');
        widget.classList.remove('is-open');
        persist();
    }

    var restored = loadState();
    sessionId = restored.sid || '';
    history = Array.isArray(restored.messages) ? restored.messages : [];
    greeted = !!restored.greeted || history.length > 0;
    blocked = !!restored.blocked;

    function startWidget() {
        renderHistory();
        beat();
        if (restored.open && history.length > 0) {
            openChat(true);
        }

        window.setInterval(beat, 8000);
        window.addEventListener('pageshow', beat);
        window.addEventListener('storage', function (event) {
            if (event.key === STORAGE_KEY && event.newValue) {
                var incoming = Object.assign(emptyState(), jsonParse(event.newValue, emptyState()));
                sessionId = incoming.sid || sessionId;
                history = Array.isArray(incoming.messages) ? incoming.messages : [];
                greeted = !!incoming.greeted || history.length > 0;
                if (incoming.blocked) {
                    hideWidget();
                    return;
                }
                renderHistory();
                try {
                    window.sessionStorage.setItem(STORAGE_KEY, event.newValue);
                } catch (err) {}
                if (incoming.open) {
                    openChat(true);
                } else if (widget.classList.contains('is-open')) {
                    closeChat();
                }
            }
        });

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

                var honey = form.querySelector('input[name="company_url"]');
                fetch('/api/chat', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': (window.PHOTOCOM && window.PHOTOCOM.csrf) || '',
                    },
                    body: JSON.stringify({
                        message: text,
                        csrf: (window.PHOTOCOM && window.PHOTOCOM.csrf) || '',
                        session_id: getSessionId(),
                        page_url: window.location.pathname + (window.location.search || ''),
                        visitor: visitorHints(),
                        company_url: honey ? honey.value : '',
                    }),
                })
                    .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
                    .then(function (result) {
                        var data = result.data || {};
                        if (data.session_id) {
                            sessionId = data.session_id;
                        }
                        if (data.blocked) {
                            if (data.reply) {
                                addMessage(data.reply, 'bot');
                            }
                            window.setTimeout(hideWidget, 1600);
                            return;
                        }
                        var reply = data.reply || data.error || config.offline || 'Merci, nous revenons vers vous rapidement.';
                        addMessage(reply, 'bot');
                    })
                    .catch(function () {
                        addMessage(config.offline || 'Service temporairement indisponible.', 'bot');
                    });
            });
        }
    }

    if (blocked) {
        fetch('/api/chat', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': (window.PHOTOCOM && window.PHOTOCOM.csrf) || '',
            },
            body: JSON.stringify({
                action: 'status',
                session_id: sessionId,
                csrf: (window.PHOTOCOM && window.PHOTOCOM.csrf) || '',
            }),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.blocked === false) {
                    blocked = false;
                    persist();
                    startWidget();
                    return;
                }
                hideWidget();
            })
            .catch(function () {
                hideWidget();
            });
    } else {
        startWidget();
    }
})();
