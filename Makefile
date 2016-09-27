install:
	make install_composer
	make install_assets

install_composer:
	composer install

install_assets:
	rm -rf ./node_modules
	rm -rf ./www/assets
	rm -rf ./www/bower_components
	npm install
	./node_modules/.bin/gulp build

config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
