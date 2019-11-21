#!/bin/bash

mkdir /var/alchemy/Phraseanet/tmp/locks && chown -R app:app /var/alchemy/Phraseanet/tmp
runuser app -c 'php /var/alchemy/Phraseanet/bin/console task-manager:scheduler:run'
