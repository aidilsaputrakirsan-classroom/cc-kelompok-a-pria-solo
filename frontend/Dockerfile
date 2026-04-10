# ============================================================
# Stage 1: Build Laravel dependencies
# ============================================================
FROM composer:2.7 AS builder

WORKDIR /app

# Copy only composer files first
COPY composer.json composer.lock ./

# Install dependencies and clean in single layer to avoid bloat
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-req=ext-gd --no-scripts --prefer-dist \
    && find /app/vendor -type f \( -name "*.md" -o -name "*.txt" -o -name "LICENSE*" -o -name "CHANGELOG*" -o -name "README*" \) -delete \
    && find /app/vendor -type d \( -name "tests" -o -name "Tests" -o -name "test" \) -exec rm -rf {} + 2>/dev/null || true \
    && find /app/vendor -type d \( -name "docs" -o -name "Docs" -o -name "documentation" -o -name "examples" -o -name "demo" \) -exec rm -rf {} + 2>/dev/null || true \
    && find /app/vendor -name ".git*" -exec rm -rf {} + 2>/dev/null || true \
    && find /app/vendor -type f -name "*.php~" -delete \
    && find /app/vendor -type f -name "*.bak" -delete \
    && rm -rf /root/.composer/cache

# Copy application code (vendor/ excluded by .dockerignore)
COPY . .

# Laravel production optimization
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction \
    && php artisan config:clear || true \
    && php artisan optimize 2>/dev/null || true

# ============================================================
# Stage 2: Runtime (production-optimized with PHP-FPM)
# ============================================================
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install only runtime dependencies
RUN apk add --no-cache curl freetype-dev libjpeg-turbo-dev libpng-dev nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql opcache

# Copy only necessary files from builder
COPY --from=builder /app /app

# Production optimization: clean up and set permissions
RUN rm -rf /app/tests /app/*.md /app/.git* /app/.vscode /app/.idea \
    && find /app/storage -type f -delete 2>/dev/null || true \
    && find /app/bootstrap/cache -type f -name "*.php" -delete 2>/dev/null || true \
    && addgroup -g 1000 appgroup \
    && adduser -D -u 1000 -G appgroup appuser \
    && mkdir -p /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache \
    && chown -R appuser:appgroup /app

USER appuser

EXPOSE 8000

# Use artisan serve for simplicity (in production, use php-fpm with nginx)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
