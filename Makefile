start: app

app: db deps tables basemodels
	docker-compose up -d app

deps:
	docker-compose run --rm cli composer install

depsupdate:
	docker-compose run --rm cli composer update

db:
	docker-compose up -d db

tables: db
	docker-compose run --rm cli whenavail db 3306 100 ./yii migrate --interactive=0

basemodels: db tables
	docker-compose run --rm cli whenavail db 3306 100 ./rebuildbasemodels.sh

ldap:
	docker-compose up -d ldap

ldapload: ldap
	docker-compose run --rm ldapload

rmldap:
	docker-compose kill ldap
	docker-compose rm -f ldap

test: app rmldap ldap ldapload
	docker-compose run --rm test

clean:
	docker-compose kill
	docker system prune -f
