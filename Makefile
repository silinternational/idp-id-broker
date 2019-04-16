start: app

app: db deps
	docker-compose up -d app cron phpmyadmin

appfortests: db deps
	docker-compose up -d appfortests

bash:
	docker-compose run --rm cli bash

deps: api.html
	docker-compose run --rm cli composer install

depsupdate: api.html
	docker-compose run --rm cli composer update

db:
	docker-compose up -d db

tables: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

basemodels: db tables
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

quicktest:
	docker-compose run --rm test bash -c "vendor/bin/behat --stop-on-failure --strict --append-snippets"

test: appfortests api.html
	docker-compose run --rm test

clean:
	docker-compose kill
	docker system prune -f

raml2html: api.html

api.html: api.raml
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."
