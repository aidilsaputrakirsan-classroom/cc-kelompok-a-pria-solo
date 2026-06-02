<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status — PRIA Solo</title>
    <link rel="stylesheet" href="{{ asset('css/status-page.css') }}">
</head>
<body>
    <div class="status-page">
        <header class="status-header">
            <div>
                <h1>System Status</h1>
                <p class="status-subtitle">Real-time health monitoring — auto-refresh every 10 seconds</p>
            </div>
            <div class="status-meta">
                <span id="refresh-indicator" class="refresh-indicator" aria-live="polite">Checking…</span>
                <span id="last-checked" class="last-checked">Last checked: —</span>
            </div>
        </header>

        <div id="service-grid" class="service-grid">
            <article class="service-card" data-service="gateway">
                <div class="service-card-header">
                    <h2>API Gateway</h2>
                    <span class="status-badge" data-status>—</span>
                </div>
                <div class="service-metrics" data-metrics></div>
            </article>

            <article class="service-card" data-service="document-service">
                <div class="service-card-header">
                    <h2>Document Service</h2>
                    <span class="status-badge" data-status>—</span>
                </div>
                <div class="error-rate-chart" data-chart hidden>
                    <div class="chart-label">Error rate (1 min window)</div>
                    <div class="chart-bar-track">
                        <div class="chart-bar-fill" data-bar style="width: 0%"></div>
                    </div>
                    <div class="chart-value" data-chart-value>0%</div>
                </div>
                <div class="service-metrics" data-metrics></div>
            </article>

            <article class="service-card" data-service="frontend">
                <div class="service-card-header">
                    <h2>Laravel Frontend</h2>
                    <span class="status-badge" data-status>—</span>
                </div>
                <div class="error-rate-chart" data-chart hidden>
                    <div class="chart-label">Error rate (1 min window)</div>
                    <div class="chart-bar-track">
                        <div class="chart-bar-fill" data-bar style="width: 0%"></div>
                    </div>
                    <div class="chart-value" data-chart-value>0%</div>
                </div>
                <div class="service-metrics" data-metrics></div>
            </article>
        </div>

        <footer class="status-footer">
            <a href="{{ url('/') }}">← Back to home</a>
            &nbsp;·&nbsp;
            <a href="{{ url(config('admin.route.prefix', 'admin')) }}">OpenAdmin</a>
        </footer>
    </div>

    <script>
        window.PRIA_GATEWAY_BASE_URL = @json($gatewayBase);
    </script>
    <script src="{{ asset('js/status-page.js') }}"></script>
</body>
</html>
