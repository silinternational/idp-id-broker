start: app

app: db composer
	docker-compose up -d app phpmyadmin

appfortests: testdb composerfortests
	docker-compose up -d appfortests

bash:
	docker-compose run --rm cli bash

composer:
	docker-compose run --rm cli composer install

composerfortests:
	docker-compose run --rm appfortests composer install
	docker-compose run --rm dynamorestart composer install

composershow:
	docker-compose run --rm cli bash -c 'composer show --format=json --no-dev --no-ansi --locked | jq "[.locked[] | { \"name\": .name, \"version\": .version }]" > dependencies.json'

composerupdate:
	docker-compose run --rm cli composer update
	make composershow

db:
	docker-compose up -d db

testdb:
	docker-compose up -d testdb

tables: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

tablesfortests: testdb
	docker-compose run --rm appfortests whenavail testdb 3306 100 ./yii migrate --interactive=0

basemodels: db tables
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

quicktest:
	docker-compose run --rm test bash -c "vendor/bin/behat --stop-on-failure --strict --append-snippets"

test: appfortests
	docker-compose run --rm test

testcli: appfortests tablesfortests mfaapi
	docker-compose run --rm test bash

mfaapi:
	docker-compose up -d mfaapi

# This is needed to re-run certain feature tests in testcli without stopping that container.
dynamoclean:
	docker-compose kill dynamorestart
	docker-compose up -d dynamorestart

clean:
	docker-compose kill
	docker-compose rm -f

raml2html:
	touch api.html
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."

callGA: app
	docker-compose exec app bash -c "./yii ga/register_event"


