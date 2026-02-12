#!/bin/sh

set -e

WWWUSER=${WWWUSER:-1000}
WWWGROUP=${WWWGROUP:-1000}

# create group if it doesn't exist
if ! getent group "$WWWGROUP" >/dev/null; then
    groupadd -g "$WWWGROUP" appgroup
fi

# create user if it doesn't exist
if ! id -u "$WWWUSER" >/dev/null 2>&1; then
    useradd -u "$WWWUSER" -g "$WWWGROUP" -s /bin/bash -m appuser
fi

# create log directory and file
mkdir -p /var/log
touch /var/log/php-fpm.log
chown "$WWWUSER:$WWWGROUP" /var/log/php-fpm.log

# fallback to php-fpm
if [ "$1" = "php-fpm" ]; then
  exec gosu "$WWWUSER" php-fpm
else
  exec gosu "$WWWUSER" "$@"
fi
