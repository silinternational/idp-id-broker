start: app

app: db deps tables basemodels
	docker-compose up -d app

deps:
	docker-compose run --rm cli composer install

depsupdate:
	docker-compose run --rm cli composer update

depsrefresh: depsupdate deps

db:
	docker-compose up -d db

tables: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

basemodels: db tables
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

tests: app
	docker-compose run --rm cli bash -c 'vendor/bin/behat'

testsupdate:
	docker-compose run --rm cli bash -c 'vendor/bin/behat --append-snippets'

clean:
	docker-compose kill
	docker system prune -f
