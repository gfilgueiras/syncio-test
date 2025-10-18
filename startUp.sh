#!/bin/bash
docker compose build --no-cache; docker compose up -d;
docker exec -w /var/www/syncio/backend sync_php composer install;
docker exec -w /var/www/syncio/backend sync_php cp .env.example .env;
docker exec -w /var/www/syncio/backend sync_php php artisan key:generate;
docker exec sync_node sh -c "cd /app && npm install && npm run build";
docker compose restart nginx;
docker exec -d sync_node sh -c "cd /app && npm run dev -- --host 0.0.0.0";
docker exec -w /var/www/syncio/backend sync_php php artisan migrate;
docker exec -w /var/www/syncio/backend sync_php bash -c "chmod -R 777 storage && chmod -R 775 database && chown -R www-data:www-data database";
