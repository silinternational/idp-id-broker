#!/usr/bin/env bash

# Try to install composer dev dependencies
cd /data
composer install --no-interaction --no-scripts --no-progress

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Try to run database migrations
whenavail appfortestsdb 3306 100 ./yii migrate --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# start apache
apachectl start

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Run the feature tests
./vendor/bin/behat --strict

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi
