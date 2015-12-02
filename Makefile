# vim:noexpandtab:ts=4:sts=4:ft=make:

install:
	php bin/console system:clear-cache
	npm install
	composer install -o
	./node_modules/.bin/grunt install-assets
	php bin/console system:clear-cache
	php bin/developer assets:compile-less

config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
