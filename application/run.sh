#!/usr/bin/env bash

# print script lines as they are executed
set -x

# exit if any line in the script fails
set -e

if [[ $APP_ENV == "dev" ]]; then
    export XDEBUG_CONFIG="remote_enable=1 remote_host="$REMOTE_DEBUG_IP
    apt-get -y -q install php-xdebug
fi

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/

echo 'sleeping a random number of seconds up to 9 to avoid migration runs clash'
sleep $[ ( $RANDOM % 10 ) ]s

if [[ $PARAMETER_STORE_PATH ]]; then
  config="config-shim --path $PARAMETER_STORE_PATH"
elif [[ $APP_ID ]]; then
  config="config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID"
else
  config=""
fi

$config /data/yii migrate --interactive=0

if [[ $RUN_TASK ]]; then
  $config ./yii $RUN_TASK
  exit $?
fi

$config apache2ctl -k start -D FOREGROUND
