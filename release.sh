#!/bin/bash

echo "Running database migrations..."
php artisan migrate --force --no-interaction

echo "Deployment completed successfully!"
