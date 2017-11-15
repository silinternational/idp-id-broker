#!/usr/bin/env bash

if [[ "x" == "x$LOGENTRIES_KEY" ]]; then
    echo "Missing LOGENTRIES_KEY environment variable";
else
    # Set logentries key based on environment variable
    sed -i /etc/rsyslog.conf -e "s/LOGENTRIESKEY/${LOGENTRIES_KEY}/"
    # Start syslog
    rsyslogd
    sleep 10
fi

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/

# Run database migrations
runny /data/yii migrate --interactive=0

# Dump env to a file
touch /etc/cron.d/broker
env | while read line ; do
   echo "$line" >> /etc/cron.d/broker
done

# Add env vars to idp-cron to make available to scripts
cat /etc/cron.d/broker-cron >> /etc/cron.d/broker

# Remove original cron file without env vars
rm -f /etc/cron.d/broker-cron

# Start cron daemon
cron -f
