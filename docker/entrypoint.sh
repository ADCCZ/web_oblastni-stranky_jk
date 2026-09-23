#!/bin/sh
set -e

# Railway (and similar PaaS) assign a random external port via $PORT.
# Locally (docker-compose) it stays 80 and we map it in docker-compose.yml.
PORT="${PORT:-80}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground
