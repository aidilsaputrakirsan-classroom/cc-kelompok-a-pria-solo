/**
 * Poll document-service /public via gateway and show degraded banner (Modul 13).
 */
(function (global) {
    const POLL_INTERVAL_MS = 60000;
    const PUBLIC_PATH = "/api/python/public";

    function getGatewayBase() {
        if (global.PRIA_GATEWAY_BASE_URL) {
            return String(global.PRIA_GATEWAY_BASE_URL).replace(/\/$/, "");
        }
        if (global.location && global.location.origin) {
            return global.location.origin;
        }
        return "";
    }

    function getBannerEl() {
        return document.getElementById("service-degraded-banner");
    }

    function showBanner() {
        const el = getBannerEl();
        if (el) {
            el.classList.remove("d-none");
        }
    }

    function hideBanner() {
        const el = getBannerEl();
        if (el) {
            el.classList.add("d-none");
        }
    }

    async function checkServiceHealth() {
        const base = getGatewayBase();
        const url = `${base}${PUBLIC_PATH}`;

        try {
            const response = await fetch(url, { method: "GET", headers: { Accept: "application/json" } });
            if (!response.ok) {
                showBanner();
                return { degraded: true };
            }
            const data = await response.json();
            const degraded =
                data.status === "degraded" ||
                (data.features && data.features.document_review === false);
            if (degraded) {
                showBanner();
            } else {
                hideBanner();
            }
            return { degraded, data };
        } catch (err) {
            showBanner();
            return { degraded: true, error: err };
        }
    }

    function bindRetry() {
        const btn = document.getElementById("service-retry-btn");
        if (!btn) return;
        btn.addEventListener("click", function () {
            checkServiceHealth();
            if (global.PriaServiceHealth && typeof global.PriaServiceHealth.onRetry === "function") {
                global.PriaServiceHealth.onRetry();
            }
        });
    }

    function init() {
        bindRetry();
        checkServiceHealth();
        setInterval(checkServiceHealth, POLL_INTERVAL_MS);
    }

    global.PriaServiceHealth = {
        check: checkServiceHealth,
        showBanner,
        hideBanner,
        onRetry: null,
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(typeof window !== "undefined" ? window : globalThis);
