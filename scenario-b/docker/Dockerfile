# Stage 1: Builder
FROM php:8.3-cli-alpine AS builder

# apcu is shared memory. The Prometheus client keeps its counters there, so the
# numbers survive between requests.
RUN apk add --no-cache $PHPIZE_DEPS postgresql-dev \
 && docker-php-ext-install pdo_pgsql \
 && yes "" | pecl install apcu \
 && docker-php-ext-enable apcu \
 && printf 'apc.enable_cli=1\napc.shm_size=32M\n' > /usr/local/etc/php/conf.d/zz-apcu.ini

# Copy Composer from official Composer image, so that we don't need to install it in the builder, this reduces the final image size
# COPY --from=... copies from another Docker image
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# This sets the working directory inside the Docker image, server will have the projects in app, but docker will have the project in build directory
WORKDIR /build

# Dependencies first, code second. This order is on purpose.
#
# Docker reuses a step only when nothing above it changed. composer.json and
# composer.lock change rarely. PHP code changes on every push. If the code
# were copied first, every push would throw away the download below and pay
# for it again. This way the download is reused.
COPY app/composer.json app/composer.lock ./

# --no-scripts and --no-autoloader: Laravel's after-install script needs the
# app code, which is not here yet. The autoloader is built further down,
# once the code has arrived.
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader

# Now the application code. This step changes on every push, but it is only
# a file copy, so it is cheap.
COPY app/ ./

# Build the optimized autoloader. This also runs Laravel's post-autoload-dump
# script (package:discover), which needs the code copied above.
RUN composer dump-autoload --no-dev --optimize --no-interaction \
 && rm -rf tests

# Stage 2: Runtime
FROM php:8.3-cli-alpine

# libpq is the PostgreSQL client library required by the compiled pdo_pgsql PHP extension.
# Creates a non-root user
# -D: Create the user with default settings and no password.
# -H: Do not create a home directory.
# -u 10001: Give the user ID 10001.
# emarat: The username.
RUN apk add --no-cache libpq \
 && adduser -D -H -u 10001 emarat

# Copy PHP extensions and configuration from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Set working directory and copy application code
WORKDIR /var/www/html

# Copy application code from builder with proper ownership to the newly created user emarat
COPY --from=builder --chown=emarat:emarat /build/ ./

# Version string baked into the image. Build with:
#   docker build --build-arg APP_VERSION=v2 ...
# Placed here on purpose: it only rebuilds the last tiny layers, not the whole image.
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

# Switch to non-root user
USER emarat
EXPOSE 8080

# Check that Laravel is responding without installing curl.
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8080/up') === false ? 1 : 0);"]

# Start the Laravel application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080", "--no-reload"]

COPY app/nonexistent-file /build/
