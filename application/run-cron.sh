#!/usr/bin/env bash

# exit if any line in the script fails
set -e

if [[ $PARAMETER_STORE_PATH ]]; then
  config="config-shim -v --path $PARAMETER_STORE_PATH"
elif [[ $APP_ID ]]; then
  config="config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID"
else
  config=""
fi

# Make environment variables available to cron jobs
$config env >> /etc/environment

if [[ -n "$SSL_CA_BASE64" ]]; then
    # Decode the base64 and write to the file
    caFile="/data/console/runtime/ca.pem"
    echo "$SSL_CA_BASE64" | base64 -d > "$caFile"
    if [[ $? -ne 0 || ! -s "$caFile" ]]; then
        echo "Failed to write database SSL certificate file: $caFile" >&2
        exit 1
    fi
fi

echo '* * * * * root /data/yii send/send-queued-email > /proc/1/fd/1 2>&1' > /etc/crontab
chmod 0644 /etc/crontab

# run cron in foreground so output is logged
cron -f
