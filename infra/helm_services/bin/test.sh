#!/bin/bash

helm uninstall all1

n=0
until [ "$n" -ge 50 ]; do
  helm install all1 ./all -f sample.yaml && break
  n=$((n+1))
  sleep 2
done
