#!/usr/bin/env bash

#!/usr/bin/env bash
export DOCKER_FILE=cypress/docker-compose.yml

# Bring up the site container
docker-compose -f $DOCKER_FILE up -d
sleep 20
docker-compose -f $DOCKER_FILE run --rm cli wp core install --url=http://localhost:8056 --title=SandboxSite --admin_user=admin --admin_password=admin --admin_email=admin@admin.com
docker-compose -f $DOCKER_FILE run --rm -u root cli chmod 0777 -R /var/www/html
docker-compose -f $DOCKER_FILE run --rm cli wp theme install neve
docker-compose -f $DOCKER_FILE run --rm cli wp theme activate neve

npm run cypress:run || exit 1

## Bring down the site container.
docker-compose -f $DOCKER_FILE down

