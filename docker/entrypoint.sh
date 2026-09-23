#!/bin/sh
set -e

# Railway (and similar PaaS) assign a random external port via $PORT.
# Locally (docker-compose) it stays 80 and we map it in docker-compose.yml.
PORT="${PORT:-80}"

sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# Railway's runtime re-enables mpm_event on some deploys regardless of what's
# baked into the image, causing "AH00534: More than one MPM loaded" crash
# loops. Force prefork (required by mod_php) again right before starting.
a2dismod mpm_event mpm_worker 2>/dev/null || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod mpm_prefork >/dev/null

exec apache2-foreground
