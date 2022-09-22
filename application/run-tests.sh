#!/usr/bin/env bash

# Try to install composer dev dependencies
cd /data
composer install --no-interaction --no-scripts --no-progress

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# avoid having issues here due to the random sleep on the appfortests container
i=10
while [ "$i" -ne 0 ]
do
  want='Page not found.'
  cRes="$(curl appfortests)"
  if [[ "$cRes" == *"$want"* ]]; then
    break
  fi
  echo "Waiting for appfortests container " $i
  sleep 1
  ((i=i-1))
done

# Try to run database migrations
whenavail testdb 3306 100 ./yii migrate --interactive=0

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
