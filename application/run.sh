#!/usr/bin/env bash

# print script lines as they are executed
set -x

# exit if any line in the script fails
set -e

echo "starting idp-id-broker version $GITHUB_REF_NAME"

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
  config="config-shim -v --path $PARAMETER_STORE_PATH"
elif [[ $APP_ID ]]; then
  config="config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID"
else
  config=""
fi

if [[ -n "$SSL_CA_BASE64" ]]; then
    # Decode the base64 and write to the file
    caFile="/data/console/runtime/ca.pem"
    echo "$SSL_CA_BASE64" | base64 -d > "$caFile"
    if [[ $? -ne 0 || ! -s "$caFile" ]]; then
        echo "Failed to write database SSL certificate file: $caFile" >&2
        exit 1
    fi
fi

$config /data/yii migrate --interactive=0

if [[ $RUN_TASK ]]; then
  $config ./yii $RUN_TASK
  exit $?
fi

$config apache2ctl -k start -D FOREGROUND
