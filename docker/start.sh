#!/bin/sh
# Railway tells the app which port to use
PORT="${PORT:-8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# add any missing tables/columns (safe to run every start)
php spark db:prepare >/dev/null 2>&1 || true
exec apache2-foreground
