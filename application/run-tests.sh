#!/usr/bin/env bash

# exit if any line in the script fails
set -e

# Try to install composer dev dependencies
cd /data
composer install --no-interaction --no-scripts --no-progress

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# avoid having issues locally due to the random sleep on the appfortests container
testServer=${TEST_SERVER_HOSTNAME}
localServer='appfortests'

if [[ "$testServer" == "$localServer" ]]; then
  whenavail $localServer 80 10 true
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

# Try to run database migrations
whenavail testdb 3306 100 ./yii migrate --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# start apache
apachectl start

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Run the feature tests
./vendor/bin/behat --strict --stop-on-failure

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi
