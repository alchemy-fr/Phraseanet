# vim:noexpandtab:ts=4:sts=4:ft=make:

install:
	npm install
	composer install -o
	php bin/console system:clear-cache

config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

test:
	@exit 0

.PHONY: install config watch test
