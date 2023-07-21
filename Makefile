help:
	@egrep "^#" Makefile

# target: docker-build|db               - Setup/Build PHP & (node)JS dependencies
db: docker-build
docker-build: build-back

build-back:
	docker-compose run --rm php sh -c "composer install"

build-back-prod:
	docker-compose run --rm php sh -c "composer install --no-dev -o"

build-zip:
	cp -Ra $(PWD) /tmp/pscriticalcss
	rm -rf /tmp/pscriticalcss/.ddev
	rm -rf /tmp/pscriticalcss/.env.test
	rm -rf /tmp/pscriticalcss/.php_cs.*
	rm -rf /tmp/pscriticalcss/.travis.yml
	rm -rf /tmp/pscriticalcss/cloudbuild.yaml
	rm -rf /tmp/pscriticalcss/composer.*
	rm -rf /tmp/pscriticalcss/package.json
	rm -rf /tmp/pscriticalcss/.npmrc
	rm -rf /tmp/pscriticalcss/package-lock.json
	rm -rf /tmp/pscriticalcss/.gitignore
	rm -rf /tmp/pscriticalcss/deploy.sh
	rm -rf /tmp/pscriticalcss/.editorconfig
	rm -rf /tmp/pscriticalcss/.git
	rm -rf /tmp/pscriticalcss/.github
	rm -rf /tmp/pscriticalcss/_dev
	rm -rf /tmp/pscriticalcss/tests
	rm -rf /tmp/pscriticalcss/docker-compose.yml
	rm -rf /tmp/pscriticalcss/Makefile
	mv -v /tmp/pscriticalcss $(PWD)/pscriticalcss
	zip -r pscriticalcss.zip pscriticalcss
	rm -rf $(PWD)/pscriticalcss

# target: build-zip-prod                   - Launch prod zip generation of the module (will not work on windows)
build-zip-prod: build-back-prod build-zip