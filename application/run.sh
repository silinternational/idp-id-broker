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

if [[ -z "${APP_ID}" ]]; then
  /data/yii migrate --interactive=0

  if [[ ! -z $RUN_TASK ]]; then
    ./yii $RUN_TASK
    exit $?
  fi

  apache2ctl -k start -D FOREGROUND
else
  config-shim -v --app $APP_ID --config $CONFIG_ID --env $ENV_ID /data/yii migrate --interactive=0

  if [[ ! -z $RUN_TASK ]]; then
    config-shim -v --app $APP_ID --config $CONFIG_ID --env $ENV_ID ./yii $RUN_TASK
    exit $?
  fi

  config-shim -v --app $APP_ID --config $CONFIG_ID --env $ENV_ID apache2ctl -k start -D FOREGROUND
fi
