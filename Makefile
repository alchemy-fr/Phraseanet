install:
	composer install -o
	rm -rf ./node_modules
	npm install
	php bin/console system:clear-cache

config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
