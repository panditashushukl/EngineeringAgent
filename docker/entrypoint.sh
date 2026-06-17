#!/bin/sh

role=${CONTAINER_ROLE:-app}

if [ "$role" = "queue" ]; then

    echo "Running the queue worker..."
    exec php artisan queue:work --verbose --tries=3 --timeout=90

else

    # If APP_KEY is not set, generate it
    if [ -z "$APP_KEY" ]; then
        echo "Generating Application Key..."
        php artisan key:generate --no-interaction --force
    fi

    # Run database migrations
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction

    # Optimize caches
    echo "Caching configuration and routes..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Start Apache
    echo "Starting Apache..."
    exec apache2-foreground

fi
