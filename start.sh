#!/bin/bash

set -e

# Wait for RabbitMQ to be ready
until curl --output /dev/null --silent --fail http://rabbitmq:15672; do
    echo 'Waiting for RabbitMQ...'
    sleep 1
done

curl -i -u guest:guest -H "content-type:application/json" \
  -XPUT -d'{"type":"direct","auto_delete":false,"durable":true}' \
  http://guest:guest@rabbitmq:15672/api/exchanges/%2f/db_insert_exchange

curl -i -u guest:guest -H "content-type:application/json" \
  -XPUT -d'{"auto_delete":false,"durable":true}' \
  http://guest:guest@rabbitmq:15672/api/queues/%2f/db_insert_queue

composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
service nginx start
php-fpm -R
