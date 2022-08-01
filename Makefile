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

depsshow:
	docker-compose run --rm cli bash -c "composer show -Df json > versions.json"

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

test: appfortests dynamoinit
	docker-compose run --rm test

testcli: appfortests tablesfortests externalapi
	docker-compose run --rm test bash

externalapi:
	docker-compose up -d external_api

clean:
	docker-compose kill
	docker system prune -f

raml2html: api.html

api.html: api.raml
	docker-compose run --rm raml2html

psr2:
	docker-compose run --rm cli bash -c "vendor/bin/php-cs-fixer fix ."


dynamo:
	docker-compose up -d dynamo

dynamoinit: dynamo wait5 createwebauthntable createapikeytable wait3 addApiKey addWebauthn

wait3:
	sleep 3

wait5:
	sleep 5

createwebauthntable:
	-AWS_ENDPOINT=http://localhost:8000 AWS_DEFAULT_REGION=local AWS_ACCESS_KEY_ID=abc123 AWS_SECRET_ACCESS_KEY=abc123 AWS_PAGER="" aws dynamodb create-table \
        --table-name WebAuthn \
        --billing-mode PAY_PER_REQUEST \
        --attribute-definitions AttributeName=uuid,AttributeType=S \
        --key-schema AttributeName=uuid,KeyType=HASH

createapikeytable:
	-AWS_ENDPOINT=http://localhost:8000 AWS_DEFAULT_REGION=local AWS_ACCESS_KEY_ID=abc123 AWS_SECRET_ACCESS_KEY=abc123 AWS_PAGER="" aws dynamodb create-table \
        --table-name ApiKey \
        --billing-mode PAY_PER_REQUEST \
        --attribute-definitions AttributeName=value,AttributeType=S \
        --key-schema AttributeName=value,KeyType=HASH

# add a test ApiKey value = EC7C2E16-5028-432F-8AF2-A79A64CF3BC1, secret = 1ED18444-7238-410B-A536-D6C15A3C
addApiKey:
	-AWS_ENDPOINT=http://localhost:8000 AWS_DEFAULT_REGION=local AWS_ACCESS_KEY_ID=abc123 AWS_SECRET_ACCESS_KEY=abc123 aws dynamodb put-item \
		--table-name ApiKey \
		--item '{"value": {"S": "EC7C2E16-5028-432F-8AF2-A79A64CF3BC1"},"hashedApiSecret": {"S": "$$2y$$10$$HtvmT/nnfofEhoFNmtk/9OfP4DDJvjzSa5dVhtOKolwb8hc6gJ9LK"},"activatedAt": {"N": "1590518082000"},"createdAt": {"N": "1590518082000"},"email": {"S": "example-user@example.com"}}'

# add a test webAuthn entry, the uuid needs to match what is expected by the tests
addWebauthn:
	-AWS_ENDPOINT=http://localhost:8000 AWS_DEFAULT_REGION=local AWS_ACCESS_KEY_ID=abc123 AWS_SECRET_ACCESS_KEY=abc123 aws dynamodb put-item \
		--table-name WebAuthn \
		--item '{"apiKey": {"S": "EC7C2E16-5028-432F-8AF2-A79A64CF3BC1"},"uuid": {"S": "097791bf-2385-4ab4-8b06-14561a338d8e"},"EncryptedAppId": {"S": "someEncryptedAppID"},"EncryptedKeyHandle": {"S": "SomeEncryptedKeyHandle"}}'

showapikeys:
	aws dynamodb scan \
      --table-name ApiKey \
      --endpoint-url http://localhost:8000 \
      --region localhost

showwebauth:
	aws dynamodb scan \
      --table-name WebAuthn \
      --endpoint-url http://localhost:8000 \
      --region localhost
