#!/bin/bash

# Fix permissions for volume mounts
chown -R appuser:appuser /var/log/php /composer /npm 2>/dev/null || true
chmod -R 755 /var/log/php 2>/dev/null || true

# Switch to appuser and exec php-fpm
exec gosu appuser php-fpm