#!/usr/bin/env bash

if [[ "x" == "x$LOGENTRIES_KEY" ]]; then
    echo "Missing LOGENTRIES_KEY environment variable";
else
    # Set logentries key based on environment variable
    sed -i /etc/rsyslog.conf -e "s/LOGENTRIESKEY/${LOGENTRIES_KEY}/"
    # Start syslog
    rsyslogd
    
    # Give syslog time to fully start up.
    sleep 3
fi

# Run database migrations
output=$(/data/yii migrate --interactive=0 2>&1)

# If the migrations failed, exit.
rc=$?;
if [[ $rc != 0 ]]; then
  logger -p 1 -t application.crit "Migrations FAILED. Exit code ${rc}. Message: ${output}"
  exit $rc;
fi

# Run apache in foreground
apache2ctl -D FOREGROUND
