#!/usr/bin/env bash

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/

# Run database migrations
runny /data/yii migrate --interactive=0

# Dump env to a file
env >> /etc/environment

# Start cron daemon
cron -f
