#!/bin/sh
# Only one Apache "MPM" may be loaded, or Apache refuses to start on Railway
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*

# Railway tells the app which port to use
PORT="${PORT:-8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
echo "ServerName localhost" > /etc/apache2/conf-enabled/servername.conf

# make sure the site can write its cache, sessions and logs
mkdir -p writable/cache writable/logs writable/session writable/debugbar
chown -R www-data:www-data writable uploads 2>/dev/null || true

# add any missing tables/columns (safe to run every start)
php spark db:prepare >/dev/null 2>&1 || true

echo "HiNoida starting on port ${PORT}"
exec apache2-foreground
