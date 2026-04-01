# ============================================================
# Stage 1: Build Laravel dependencies
# ============================================================
FROM composer:2.7 AS builder

WORKDIR /app

# Copy only composer files first
COPY composer.json composer.lock ./

# Install dependencies (creates vendor/)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --ignore-platform-req=ext-gd --no-scripts

# Copy application code (vendor/ excluded by .dockerignore)
COPY . .

# Optimize and clean up
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan config:clear || true \
    && rm -rf /root/.composer/cache

# ============================================================
# Stage 2: Runtime (optimized)
# ============================================================
FROM php:8.2-cli-alpine

WORKDIR /app

# Install only runtime dependencies
RUN apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Copy only necessary files from builder
COPY --from=builder /app /app

# Clean up unnecessary files and set permissions
RUN rm -rf /app/tests /app/*.md /app/.git* /app/.vscode /app/.idea \
    && find /app/storage -type f -delete 2>/dev/null || true \
    && addgroup -g 1000 appgroup \
    && adduser -D -u 1000 -G appgroup appuser \
    && mkdir -p /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache \
    && chown -R appuser:appgroup /app

USER appuser

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
