#!/bin/bash

echo "Setting up Laravel for Heroku..."

# Clear any cached config/routes from build
php artisan config:clear
php artisan route:clear

echo "Running database migrations..."
php artisan migrate --force --no-interaction

# Cache config and routes for performance
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache

echo "Deployment completed successfully!"
