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

    global.PriaGateway = {
        fetch: gatewayFetch,
        unavailableMessage: DEFAULT_UNAVAILABLE,
        setBaseUrl: function (url) {
            global.PRIA_GATEWAY_BASE_URL = url;
        },
    };
})(typeof window !== "undefined" ? window : globalThis);
