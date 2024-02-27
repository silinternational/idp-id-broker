#!/usr/bin/env bash

# print script lines as they are executed
set -x

# exit if any line in the script fails
set -e

# establish a signal handler to catch the SIGTERM from a 'docker stop'
# reference: https://medium.com/@gchudnov/trapping-signals-in-docker-containers-7a57fdda7d86
term_handler() {
  apache2ctl stop
  exit 143; # 128 + 15 -- SIGTERM
}
trap 'kill ${!}; term_handler' SIGTERM

if [[ $APP_ENV == "dev" ]]; then
    export XDEBUG_CONFIG="remote_enable=1 remote_host="$REMOTE_DEBUG_IP
    apt-get -y -q install php-xdebug
fi

# fix folder permissions
chown -R www-data:www-data \
  /data/console/runtime/

echo 'sleeping a random number of seconds up to 9 to avoid migration runs clash'
sleep $[ ( $RANDOM % 10 ) ]s


# Run database migrations
config-shim -v --app $APP_ID --config $CONFIG_ID --env $ENV_ID /data/yii migrate --interactive=0

if [[ ! -z $RUN_TASK ]]; then
    ./yii $RUN_TASK
    exit $?
fi

config-shim -v --app $APP_ID --config $CONFIG_ID --env $ENV_ID apache2ctl -k start -D FOREGROUND

# endless loop with a wait is needed for the trap to work
while true
do
  tail -f /dev/null & wait ${!}
done

