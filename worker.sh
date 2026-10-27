#!/bin/bash
# Heroku worker: process the queue until it's empty, then scale this dyno down to 0.
# If jobs arrived in the meantime the dyno stays up and Heroku restarts this script.
php artisan queue:work --stop-when-empty --tries=3 --timeout=1800
php artisan worker:scale-down
