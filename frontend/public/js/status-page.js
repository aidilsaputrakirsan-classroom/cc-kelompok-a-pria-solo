/**
 * System status dashboard (Modul 14) — polls health + metrics via API gateway.
 */
(function () {
    const POLL_INTERVAL_MS = 10000;

    const SERVICES = [
        {
            key: 'gateway',
            cardSelector: '[data-service="gateway"]',
            healthUrl: '/health',
            metricsUrl: null,
        },
        {
            key: 'document-service',
            cardSelector: '[data-service="document-service"]',
            healthUrl: '/api/python/health',
            metricsUrl: '/api/python/metrics',
        },
        {
            key: 'frontend',
            cardSelector: '[data-service="frontend"]',
            healthUrl: '/frontend/health',
            metricsUrl: '/frontend/metrics',
        },
    ];

    const STATUS_COLORS = {
        healthy: '#22c55e',
        degraded: '#f59e0b',
        unhealthy: '#ef4444',
        unreachable: '#6b7280',
    };

    function gatewayBase() {
        if (window.PRIA_GATEWAY_BASE_URL) {
            return String(window.PRIA_GATEWAY_BASE_URL).replace(/\/$/, '');
        }
        return window.location.origin;
    }

    function setRefreshIndicator(text, loading) {
        const el = document.getElementById('refresh-indicator');
        if (!el) return;
        el.textContent = text;
        el.classList.toggle('is-loading', Boolean(loading));
    }

    function setLastChecked() {
        const el = document.getElementById('last-checked');
        if (el) {
            el.textContent = 'Last checked: ' + new Date().toLocaleTimeString();
        }
    }

    async function fetchJson(path) {
        const url = gatewayBase() + path;
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json().catch(() => ({}));
        return { ok: response.ok, status: response.status, data };
    }

    function renderMetrics(container, metrics) {
        if (!metrics) {
            container.innerHTML = '<p class="metric-muted">Metrics unavailable</p>';
            return;
        }

        const latency = metrics.latency || {};
        container.innerHTML = `
            <div class="metric-grid">
                <div>Requests: <strong>${metrics.total_requests ?? 0}</strong></div>
                <div>Errors: <strong class="${(metrics.total_errors ?? 0) > 0 ? 'text-error' : ''}">${metrics.total_errors ?? 0}</strong></div>
                <div>Error rate: <strong>${metrics.error_rate_percent ?? 0}%</strong></div>
                <div>Avg latency: <strong>${latency.avg_ms ?? 0}ms</strong></div>
                <div>p95 latency: <strong>${latency.p95_ms ?? 0}ms</strong></div>
                <div>Uptime: <strong>${Math.round((metrics.uptime_seconds ?? 0) / 60)} min</strong></div>
            </div>
        `;
    }

    function renderErrorChart(card, metrics) {
        const chart = card.querySelector('[data-chart]');
        if (!chart || !metrics) {
            if (chart) chart.hidden = true;
            return;
        }

        const rate = metrics.error_rate_last_minute_percent ?? metrics.error_rate_percent ?? 0;
        const bar = chart.querySelector('[data-bar]');
        const value = chart.querySelector('[data-chart-value]');

        chart.hidden = false;
        if (bar) {
            bar.style.width = Math.min(rate, 100) + '%';
            bar.classList.toggle('is-critical', rate > 10);
        }
        if (value) {
            value.textContent = rate + '%';
        }
    }

    function updateCard(service, healthResult, metricsResult) {
        const card = document.querySelector(service.cardSelector);
        if (!card) return;

        const badge = card.querySelector('[data-status]');
        const metricsEl = card.querySelector('[data-metrics]');

        let status = 'unreachable';
        if (healthResult.ok && healthResult.data) {
            status = healthResult.data.status || 'healthy';
        }

        if (badge) {
            badge.textContent = status;
            badge.style.background = STATUS_COLORS[status] || STATUS_COLORS.unreachable;
        }

        card.style.borderLeftColor = STATUS_COLORS[status] || STATUS_COLORS.unreachable;

        const metrics = metricsResult && metricsResult.ok ? metricsResult.data : null;
        renderMetrics(metricsEl, metrics);
        renderErrorChart(card, metrics);
    }

    async function refreshAll() {
        setRefreshIndicator('Refreshing…', true);

        await Promise.all(
            SERVICES.map(async (service) => {
                let healthResult = { ok: false, data: null };
                let metricsResult = null;

                try {
                    healthResult = await fetchJson(service.healthUrl);
                } catch (_) {
                    healthResult = { ok: false, data: { status: 'unreachable' } };
                }

                if (service.metricsUrl) {
                    try {
                        metricsResult = await fetchJson(service.metricsUrl);
                    } catch (_) {
                        metricsResult = { ok: false, data: null };
                    }
                }

                updateCard(service, healthResult, metricsResult);
            })
        );

        setRefreshIndicator('Live', false);
        setLastChecked();
    }

    refreshAll();
    setInterval(refreshAll, POLL_INTERVAL_MS);
})();
