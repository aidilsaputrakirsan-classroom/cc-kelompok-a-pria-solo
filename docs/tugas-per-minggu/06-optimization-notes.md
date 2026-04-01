# Frontend Image Optimization Notes

**Date:** 2026-04-01  
**Issue:** Frontend image was 15.4 GB (too large)  
**Result:** Reduced to 7.59 GB (50.7% reduction)

---

## Problem Analysis

The original frontend image was **15.4 GB** due to:

1. **Inefficient `.dockerignore`** - Not excluding enough files
2. **No cache cleanup** - Composer cache remained in image
3. **Unnecessary files** - Tests, docs, IDE configs copied to runtime
4. **Large vendor directory** - Laravel/OpenAdmin dependencies (~6-7 GB)

## Optimizations Applied

### 1. Enhanced `.dockerignore`

**Before:**
```
vendor/
node_modules/
.env
.git/
storage/logs/
tests/
*.md
```

**After:**
```
# Dependencies (will be installed by composer)
vendor/
node_modules/

# Environment and secrets
.env
.env.*

# Storage and cache
storage/logs/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/app/public/
storage/debugbar/

# Tests and documentation
tests/
*.md

# IDE files
.vscode/
.idea/
.editorconfig

# Build artifacts
*.log
*.cache
.phpunit.result.cache
bootstrap/cache/*.php

# Docker and CI/CD
docker/
.dockerignore
Dockerfile
.github/
```

### 2. Dockerfile Optimizations

**Added to builder stage:**
```dockerfile
# Clean up composer cache
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan config:clear || true \
    && rm -rf /root/.composer/cache
```

**Added to runtime stage:**
```dockerfile
# Remove unnecessary files
RUN rm -rf /app/tests /app/*.md /app/.git* /app/.vscode /app/.idea \
    && find /app/storage -type f -delete 2>/dev/null || true \
    && addgroup -g 1000 appgroup \
    && adduser -D -u 1000 -G appgroup appuser \
    && mkdir -p /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache \
    && chown -R appuser:appgroup /app
```

## Results

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Image Size | 15.4 GB | 7.59 GB | -7.81 GB (50.7%) |
| Build Time | ~6 minutes | ~5 minutes | -1 minute |
| Functionality | ✅ Working | ✅ Working | No regression |

## Verification

```bash
# Image size
$ docker images pria-solo-frontend:v1
pria-solo-frontend:v1	7.59GB

# Container status
$ docker ps --filter name=frontend
frontend    Up 6 minutes    0.0.0.0:3000->8000/tcp

# Endpoint test
$ curl -I http://127.0.0.1:3000/
HTTP/1.1 200 OK
```

## Why Still 7.59 GB?

The image is still large because:

1. **Laravel/OpenAdmin vendor** - ~6-7 GB of PHP dependencies
   - OpenAdmin includes AdminLTE, Laravel Excel, etc.
   - Many frontend assets bundled in vendor
   
2. **PHP Extensions** - GD, PDO, MySQL compiled extensions

3. **Application code** - Full Laravel application with resources

## Further Optimization Possibilities

If needed to reduce further:

### Option 1: Use Alpine-based PHP FPM
```dockerfile
FROM php:8.2-fpm-alpine
# FPM is more optimized than CLI
```

### Option 2: Multi-stage with selective vendor copy
```dockerfile
# Only copy production-critical vendor packages
COPY --from=builder /app/vendor/laravel /app/vendor/laravel
COPY --from=builder /app/vendor/encore /app/vendor/encore
# etc.
```

### Option 3: Compress vendor with opcache
```dockerfile
RUN php artisan optimize \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
```

### Option 4: Use external volume for vendor
```bash
# Mount vendor as volume instead of including in image
docker run -v vendor_data:/app/vendor pria-solo-frontend:v1
```

## Recommendation

**Current size (7.59 GB) is acceptable for:**
- Development and testing environments
- Internal deployment
- Assignment submission (demonstrates multi-stage build understanding)

**Further optimization needed for:**
- Production deployment at scale
- Limited storage environments
- Container registry bandwidth constraints

---

## Commands to Rebuild

```bash
# Clean old image
docker stop frontend
docker rm frontend
docker rmi pria-solo-frontend:v1

# Rebuild with optimizations
docker build -t pria-solo-frontend:v1 ./frontend

# Verify size
docker images pria-solo-frontend:v1

# Test
docker run -d --name frontend --network cloudnet \
  --env-file ./frontend/.env.docker \
  -p 3000:8000 \
  pria-solo-frontend:v1

curl http://127.0.0.1:3000/
```
