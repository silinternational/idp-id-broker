start: app

app: db deps
	docker-compose up -d app phpmyadmin

appfortests: appfortestsdb depsfortests
	docker-compose up -d appfortests

bash:
	docker-compose run --rm cli bash

deps:
	docker-compose run --rm cli composer install

depsfortests:
	docker-compose run --rm appfortests composer install

depsshow:
	docker-compose run --rm cli bash -c "composer show -Df json > versions.json"

depsupdate:
	docker-compose run --rm cli composer update
	make depsshow

db:
	docker-compose up -d db

appfortestsdb:
	docker-compose up -d appfortestsdb

tables: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

tablesfortests: appfortestsdb
	docker-compose run --rm appfortests whenavail appfortestsdb 3306 100 ./yii migrate --interactive=0

basemodels: db tables
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

quicktest:
	docker-compose run --rm test bash -c "vendor/bin/behat --stop-on-failure --strict --append-snippets"

test: appfortests
	docker-compose run --rm test

testcli: appfortests tablesfortests
	docker-compose run --rm test bash

clean:
	docker-compose kill
	docker system prune -f

raml2html: api.html

api.html: api.raml
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."
