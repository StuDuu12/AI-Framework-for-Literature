/**
 * Tự động gắn CSRF token vào mọi request POST cùng origin (fetch + XMLHttpRequest).
 * Token được render sẵn trong <meta name="csrf-token"> bởi views/templates/assets.php.
 */
(function () {
    'use strict';

    function getToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function isSameOrigin(url) {
        if (!url) return true;
        try {
            var u = new URL(url, window.location.href);
            return u.origin === window.location.origin;
        } catch (e) {
            return true;
        }
    }

    // --- fetch ---
    var originalFetch = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || (input && input.method) || 'GET').toUpperCase();
        var url = typeof input === 'string' ? input : (input && input.url);

        if (method !== 'GET' && method !== 'HEAD' && isSameOrigin(url)) {
            var token = getToken();
            if (token) {
                if (input instanceof Request && !init.headers) {
                    input = new Request(input, { headers: appendHeader(input.headers, token) });
                } else {
                    init.headers = appendHeader(init.headers, token);
                }
            }
        }
        return originalFetch.call(this, input, init);
    };

    function appendHeader(headers, token) {
        if (headers instanceof Headers) {
            headers.set('X-CSRF-Token', token);
            return headers;
        }
        var h = headers || {};
        if (Array.isArray(h)) {
            h.push(['X-CSRF-Token', token]);
            return h;
        }
        h['X-CSRF-Token'] = token;
        return h;
    }

    // --- XMLHttpRequest ---
    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url) {
        this._csrfNeeded = String(method).toUpperCase() !== 'GET' && isSameOrigin(url);
        return originalOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function () {
        if (this._csrfNeeded) {
            var token = getToken();
            if (token) {
                try { this.setRequestHeader('X-CSRF-Token', token); } catch (e) { /* header đã set */ }
            }
        }
        return originalSend.apply(this, arguments);
    };
})();
