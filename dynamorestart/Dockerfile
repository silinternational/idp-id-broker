FROM composer:2

WORKDIR /dynamo

ENV AWS_ENDPOINT=dynamo:8000
ENV AWS_DEFAULT_REGION=us-east-1
ENV AWS_ACCESS_KEY_ID=abc123
ENV AWS_SECRET_ACCESS_KEY=abc123
ENV AWS_DISABLE_SSL="true"

COPY DynamoRestart.php /dynamo/DynamoRestart.php
COPY restart.php /dynamo/restart.php
COPY composer.json /dynamo/composer.json
RUN composer self-update && composer upgrade

EXPOSE 8080

CMD ["php restart.php"]