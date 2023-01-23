#!/bin/sh

set -xe

if [ -n "$PHRASEANET_K8S_NAMESPACE" ]; then
  echo "PHRASEANET_K8S_NAMESPACE is defined : $PHRASEANET_K8S_NAMESPACE"
  NEW_TARGET=phraseanet-saml-sp.$PHRASEANET_K8S_NAMESPACE.svc.cluster.local
  NEW_RESOLVER=kube-dns.kube-system.svc.cluster.local
else
  echo "NO PHRASEANET_K8S_NAMESPACE is defined"
  NEW_TARGET=phraseanet-saml-sp
  NEW_RESOLVER=127.0.0.11
fi

cat /nginx.conf.sample | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_SEND_TIMEOUT/$GATEWAY_SEND_TIMEOUT/g"  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_PROXY_TIMEOUT/$GATEWAY_PROXY_TIMEOUT/g"  | sed "s/\$NEW_TARGET/$NEW_TARGET/g"  | sed "s/\$NEW_RESOLVER/$NEW_RESOLVER/g" > /etc/nginx/conf.d/default.conf
cat /fastcgi_timeout.conf  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" > /etc/nginx/fastcgi_extended_params
exec "$@"
