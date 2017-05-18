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
/data/yii migrate --interactive=0

# Run apache in foreground
apache2ctl -D FOREGROUND
