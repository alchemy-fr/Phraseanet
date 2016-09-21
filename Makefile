install:
	composer install
	rm -rf ./node_modules
	rm -rf ./www/assets
	rm -rf ./www/bower_components
	npm install

config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
