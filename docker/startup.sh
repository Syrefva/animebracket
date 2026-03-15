#!/bin/bash
# Allow git in mounted /app (fixes "dubious ownership" when host dir has different uid)
git config --global --add safe.directory /app

# Install Composer deps (needed because volume mount overwrites /app, including vendor from build)
if [ ! -f ./vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader --no-dev
fi
mkdir -p ./cache
chmod 777 ./cache
mkdir ./images
chmod 777 ./images
mkdir -p ./dist/static/js/

service php7.4-fpm start
service redis-server start
service memcached start
service nginx start
cron

tail -f /var/log/nginx/animebracket.error.log
