#!/usr/bin/env bash

if [[ $PARAMETER_STORE_PATH ]]; then
  config="config-shim -v --path $PARAMETER_STORE_PATH"
elif [[ $APP_ID ]]; then
  config="config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID"
else
  config=""
fi

# Make environment variables available to cron jobs
$config env >> /etc/environment

echo '* * * * * root /data/yii send/send-queued-email > /proc/1/fd/1 2>&1' > /etc/crontab
chmod 0644 /etc/crontab

# run cron in foreground so output is logged
cron -f
