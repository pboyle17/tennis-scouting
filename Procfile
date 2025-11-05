release: bash release.sh
web: vendor/bin/heroku-php-apache2 public/
worker: php artisan queue:work --tries=3 --timeout=1800
