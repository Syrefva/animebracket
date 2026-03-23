#!/bin/bash
# Allow git access in mounted /app
git config --global --add safe.directory /app

# Install Composer deps if missing
if [ ! -f ./vendor/autoload.php ]; then
    composer install --no-interaction --optimize-autoloader --no-dev
fi
mkdir -p ./cache
chmod 777 ./cache
mkdir ./images
chmod 777 ./images
mkdir -p ./dist/static/js/

# Toggle auth snippet from PASSWORD_GATE_ENABLED
if [ "$PASSWORD_GATE_ENABLED" = "true" ]; then
  if [ ! -f /etc/nginx/.htpasswd ] || [ -d /etc/nginx/.htpasswd ]; then
    echo "Error: PASSWORD_GATE_ENABLED is true but .htpasswd is missing or invalid."
    echo "Create .htpasswd (see .htpasswd.example and README) and ensure it is mounted."
    exit 1
  fi
  cp /app/docker/auth-on.conf /etc/nginx/auth-snippet.conf
else
  : > /etc/nginx/auth-snippet.conf
fi
cp /app/docker/nginx.conf /etc/nginx/sites-enabled/default

service php7.4-fpm start
service redis-server start
service memcached start
service nginx start
cron

tail -f /var/log/nginx/animebracket.error.log
