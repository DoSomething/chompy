web: composer warmup && vendor/bin/heroku-php-nginx -C nginx.conf public/

release: php artisan migrate --force

queue: php artisan queue:work --sleep=5