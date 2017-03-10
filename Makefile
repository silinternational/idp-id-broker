start: app

app: db composer yiimigrate basemodels
	docker-compose up -d app

composer:
	docker-compose run --rm cli composer install

composerupdate:
	docker-compose run --rm cli composer update

db:
	docker-compose up -d db

yiimigrate: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

basemodels: db yiimigrate
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

test: app
	docker-compose run --rm cli bash -c 'vendor/bin/behat'

testupdate:
	docker-compose run --rm cli bash -c 'vendor/bin/behat --append-snippets'

clean:
	docker-compose kill
	docker-compose rm -f
