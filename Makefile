start: app

app: db deps
	docker-compose up -d app phpmyadmin

appfortests: testdb depsfortests
	docker-compose up -d appfortests

bash:
	docker-compose run --rm cli bash

deps:
	docker-compose run --rm cli composer install

depsfortests:
	docker-compose run --rm appfortests composer install
	docker-compose run --rm dynamorestart composer install

depsshow:
	docker-compose run --rm cli bash -c "composer show -D > versions.txt"

depsupdate:
	docker-compose run --rm cli composer update
	make depsshow

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
	docker system prune -f

raml2html:
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."

callGA: app
	docker-compose exec app bash -c "./yii ga/register_event"


