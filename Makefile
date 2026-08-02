-include .env

exec:
	docker compose exec php-fpm $$cmd

exec-root:
	docker compose exec -u root php-fpm $$cmd

execTTY:
	docker compose exec -T  php-fpm $$cmd

docker-down:
	docker compose down --remove-orphans

ssl-generate:
	mkdir -p docker/nginx/local/ssl
	mkcert -key-file docker/nginx/local/ssl/key.pem -cert-file docker/nginx/local/ssl/cert.pem localhost

# `up --build -d` reads each running container's image ID BEFORE building, so a freshly built
# image is not noticed and the change lands one deploy late — green deploy, right files on
# disk, stale behaviour inside the container. Build and start are therefore separate steps.
docker-build: ssl-generate
	docker compose build nginx-base
	docker compose build
	docker compose up -d

storage-link:
	make exec cmd="php artisan storage:link"

pull:
	git pull

update-local: docker-down pull docker-build composer-install npm-install npm-prod cache

setup: key-generate storage-link npm-install npm-prod

update-prod: pull docker-build composer-install-prod npm-ci npm-prod cache

key-generate:
	make exec cmd="php artisan key:generate"

bash:
	make exec cmd="bash"

bash-root:
	make exec-root cmd="bash"

npm-dev:
	make exec cmd="npm run dev"

migrate:
	make exec cmd="php artisan migrate"

migrate-rollback:
	make exec cmd="php artisan migrate:rollback"

migrate-fresh:
	make exec cmd="php artisan migrate:fresh"

seed-db:
	make exec cmd="php artisan db:seed"

composer-update:
	make exec cmd="composer update"

composer-install:
	make exec cmd="composer install"

composer-install-prod:
	make exec cmd="composer install --no-dev"

composer-dump:
	make exec cmd="composer dump-autoload"

npm-install:
	make exec cmd="npm install"

npm-ci:
	make exec cmd="npm ci"

npm-update:
	make exec cmd="npm update"

npm-prod:
	make exec cmd="npm run build"

npm-watch:
	make exec cmd="npm run dev"

cmd-test:
	make exec cmd="php artisan volkv:test"

# The chmod flips the executable bit on tracked files, and with core.fileMode=true git then
# reports them all as modified — thousands of phantom changes on a production checkout, inside
# which a real edit becomes invisible. Turning fileMode off makes `git status` mean something
# again; content changes still show.
perm:
	sudo chown -R 1000:1000 .
	sudo chmod -R ug+rwX .
	git config core.fileMode false

cache:
	make exec cmd="php artisan volkv:cache"

cache-noide:
	make execTTY cmd="php artisan volkv:cache --noide"

opcache-clear:
	make exec cmd="php artisan opcache:clear"

log-queue:
	docker compose logs --tail 50 -f queue-default

log-sql:
	docker compose logs --tail="50" sql

log-access:
	tail -n50 docker/volume/nginx/logs/access.log

log-scheduler:
	docker compose logs --tail="50" scheduler

log-nginx:
	docker compose logs --tail="50" nginx

backup-db:
	docker compose exec -u root -T sql bash -c "pg_dump -Fc -U ${DB_USERNAME} ${DB_DATABASE} > /backups/backup.gz && cp /backups/backup.gz /backups/old/`date +%d-%m-%Y"_"%H_%M_%S`.gz"

restore-db:
	docker compose exec -u root -T sql bash -c "dropdb --force --if-exists -U ${DB_USERNAME} ${DB_DATABASE} && createdb -U ${DB_USERNAME} ${DB_DATABASE} && pg_restore -U ${DB_USERNAME} -d ${DB_DATABASE} -j 4 /backups/backup.gz"

push-db:
	scp -i ~/.ssh/id_rsa docker/volume/postgres/backup.gz root@${SERVER_IP}:${GITHUB_REPOSITORY}/docker/volume/postgres/backup.gz

pull-db:
	scp root@${SERVER_IP}:${GITHUB_REPOSITORY}/docker/volume/postgres/backup.gz docker/volume/postgres/backup.gz

after-pull-perm:
	sudo chmod -R 775  storage/framework/views/

pull-restore-db: pull-db restore-db

deploy:
	ssh root@${SERVER_IP} 'cd ${GITHUB_REPOSITORY} && git reset --hard && make after-pull-perm && git pull https://${GITHUB_CREDENTIALS}@github.com/volkv/${GITHUB_REPOSITORY}.git && make after-pull-perm && make update-prod'

_test-pre:
	make docker-build && \
	make npm-prod && \
	make cache-noide

_test-all:
	make exec cmd="vendor/bin/phpunit"

_test-feature:
	make exec cmd="vendor/bin/phpunit --testsuite=Feature"

# `test` rebuilds the image and the assets first (_test-pre) — correct before shipping, wrong
# as the everyday check: it cannot run without the right to build, and when it fails you cannot
# tell the code from a stale or foreign image. `test-quick` runs the same suite against the
# stack as it stands. Use it to verify a change; use `test` before a release.
test-quick: _test-all

test: _test-pre _test-all
