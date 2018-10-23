web: vendor/bin/heroku-php-apache2 public

release: php artisan migrate --force

queue: php -d memory_limit=512M artisan queue:work sqs --tries=3 --sleep=5 --queue=$SQS_DEFAULT_QUEUE
