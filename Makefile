config:
	@php bin/console compile:configuration

watch:
	@echo 'config/configuration.yml' | entr make config

.PHONY: config watch
