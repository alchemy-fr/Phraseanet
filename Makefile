install:
	make install_composer
	make clean_assets
	make install_asset_dependencies
	make install_assets

install_composer:
	composer install

install_asset_dependencies:
	yarn upgrade 
	./node_modules/.bin/gulp build

install_assets:
	./node_modules/.bin/gulp install-assets

clean_assets:
	rm -rf ./node_modules
	rm -rf ./www/assets
	mkdir ./node_modules
	touch ./node_modules/.gitkeep

config:
	@php bin/console compile:configuration
	@php bin/developer orm:generate-proxies

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
