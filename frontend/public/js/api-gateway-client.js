/**
 * API Gateway fetch helper (Modul 12 — Bagian C Frontend).
 * Maps 503/504 to a user-friendly message when a microservice is unavailable.
 */
(function (global) {
    const DEFAULT_UNAVAILABLE =
        "Service temporarily unavailable. Please try again in a few minutes.";

    function getGatewayBaseUrl() {
        if (global.PRIA_GATEWAY_BASE_URL) {
            return String(global.PRIA_GATEWAY_BASE_URL).replace(/\/$/, "");
        }
        return "";
    }

    /**
     * @param {string} path - e.g. "/auth/login" or full URL
     * @param {RequestInit} [options]
     * @returns {Promise<Response>}
     */
    async function gatewayFetch(path, options) {
        const base = getGatewayBaseUrl();
        const url =
            path.startsWith("http://") || path.startsWith("https://")
                ? path
                : `${base}${path.startsWith("/") ? path : `/${path}`}`;

        let response;
        try {
            response = await fetch(url, options);
        } catch (err) {
            const error = new Error(DEFAULT_UNAVAILABLE);
            error.cause = err;
            error.code = "NETWORK_ERROR";
            throw error;
        }

        if (response.status === 503 || response.status === 504) {
            const error = new Error(DEFAULT_UNAVAILABLE);
            error.status = response.status;
            error.code = "SERVICE_UNAVAILABLE";
            throw error;
        }

        return response;
    }

    /**
     * Show a simple retry prompt for 503/504 (Modul 13).
     * @param {string} message
     * @param {function} retryFn
     */
    function promptRetry(message, retryFn) {
        const msg =
            message ||
            DEFAULT_UNAVAILABLE +
                "\n\nClick OK to retry, or Cancel to dismiss.";
        if (typeof retryFn === "function" && global.confirm(msg + "\n\nRetry now?")) {
            retryFn();
        }
    }

    global.PriaGateway = {
        fetch: gatewayFetch,
        unavailableMessage: DEFAULT_UNAVAILABLE,
        promptRetry: promptRetry,
        setBaseUrl: function (url) {
            global.PRIA_GATEWAY_BASE_URL = url;
        },
    };
})(typeof window !== "undefined" ? window : globalThis);
