#!/usr/bin/env bash
sudo ansible-playbook /vagrant/resources/ansible/playbook-always.yml -e hostname=$1 --extra-vars "{\"hostname\": \"$1\", \"postfix\": { \"postfix_domain\": \"$1.vb\" }, \"parade_var\": \"$2\" }" --connection=local
