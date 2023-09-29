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

if [ ! -z "$GATEWAY_FASTCGI_HTTPS" ]; then
  echo "GATEWAY_FASTCGI_HTTPS is defined : $GATEWAY_FASTCGI_HTTPS"
  if [ "$GATEWAY_FASTCGI_HTTPS" = "on" ] || [ "$GATEWAY_FASTCGI_HTTPS" = "1" ];then
      echo "SETTING SERVER_PORT TO: 443"
      GATEWAY_FASTCGI_HTTPS="fastcgi_param HTTPS on;fastcgi_param SERVER_PORT 443;"
  else
      GATEWAY_FASTCGI_HTTPS="fastcgi_param HTTPS $GATEWAY_FASTCGI_HTTPS;"
  fi
else
  echo "NO GATEWAY_FASTCGI_HTTPS is defined"
  GATEWAY_FASTCGI_HTTPS="fastcgi_param HTTPS on;fastcgi_param SERVER_PORT 443;"
fi

if [ ! -z "$GATEWAY_CSP" ]; then
  echo "Content Security policies is defined to : $GATEWAY_CSP"
  envsubst < "/securitycontentpolicies.sample.conf" > /etc/nginx/conf.d/securitycontentpolicies.conf
else
  echo "Content Security policies is defined"
  export GATEWAY_CSP="default-src 'self' 127.0.0.1 https://apiws.carrick-skills.com:8443 https://apiws.carrick-flow.com:8443 https://fonts.gstatic.com *.tiles.mapbox.com https://api.mapbox.com https://events.mapbox.com *.axept.io *.matomo.cloud *.newrelic.com *.nr-data.net https://www.googletagmanager.com *.google-analytics.com *.phrasea.io https://apiws.carrick-flow.com:8443 https://apiws.carrick-skills.com:8443  https://maxcdn.bootstrapcdn.com data: ; script-src 'unsafe-inline' 'unsafe-eval' 'self' https://www.gstatic.com *.alchemyasp.com *.axept.io *.matomo.cloud *.newrelic.com https://www.googletagmanager.com https://apiws.carrick-flow.com:8443 https://apiws.carrick-skills.com:8443  https://maxcdn.bootstrapcdn.com  data: blob: ; style-src 'self' 'unsafe-inline' https://fonts.gstatic.com https://fonts.googleapis.com https://www.google.com https://www.gstatic.com https://apiws.carrick-flow.com:8443 https://apiws.carrick-skills.com:8443  https://maxcdn.bootstrapcdn.com ; img-src 'self'  data: blob: *.tiles.mapbox.com https://axeptio.imgix.net *.cloudfront.net *.phrasea.io *.amazonaws.com https://apiws.carrick-flow.com:8443 https://apiws.carrick-skills.com:8443  https://maxcdn.bootstrapcdn.com https://www.gnu.org/graphics/ ; object-src 'self'; frame-ancestors 'self'"
  echo "setting Security policies to : " $GATEWAY_CSP
  envsubst < "/securitycontentpolicies.sample.conf" > /etc/nginx/conf.d/securitycontentpolicies.conf
fi

cat /nginx.conf.sample | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_SEND_TIMEOUT/$GATEWAY_SEND_TIMEOUT/g"  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" | sed "s/\$MAX_BODY_SIZE/$MAX_BODY_SIZE/g" | sed "s/\$GATEWAY_PROXY_TIMEOUT/$GATEWAY_PROXY_TIMEOUT/g"  | sed "s/\$NEW_TARGET/$NEW_TARGET/g"  | sed "s/\$NEW_RESOLVER/$NEW_RESOLVER/g" | sed "s/\$GATEWAY_FASTCGI_HTTPS/$GATEWAY_FASTCGI_HTTPS/g" > /etc/nginx/conf.d/default.conf
cat /fastcgi_timeout.conf  | sed "s/\$GATEWAY_FASTCGI_TIMEOUT/$GATEWAY_FASTCGI_TIMEOUT/g" > /etc/nginx/fastcgi_extended_params

echo `date +"%Y-%m-%d %H:%M:%S"` " - Setting for real_ip_from using Trusted Proxies"
SET_REAL_IP_FROM=""
if [[ ! -z $PHRASEANET_TRUSTED_PROXIES ]]; then
    cp proxy.conf.sample  /etc/nginx/conf.d/proxy.conf
    for i in $(echo $PHRASEANET_TRUSTED_PROXIES | sed "s/,/ /g")
        do
            #y=$(echo $i | sed "s/\//_/g")
            #SET_REAL_IP_FROM="$SET_REAL_IP_FROM"$'\n'"set_real_ip_from $i;"
            #echo "set_real_ip_from $i;" >> /etc/nginx/conf.d/proxy.conf
            echo  "set_real_ip_from $i;" | cat - /etc/nginx/conf.d/proxy.conf | tee /etc/nginx/conf.d/proxy.conf
        done
#    echo $SET_REAL_IP_FROM
#    cat proxy.conf.sample | sed "s/\$SET_REAL_IP_FROM/$SET_REAL_IP_FROM/g" > /etc/nginx/conf.d/proxy.conf
fi

#GATEWAY_ALLOWED_IPS="10.0.0.1,10.0.1.1"
#GATEWAY_DENIED_IPS="172.1.0.1,172.1.0.2"
#GATEWAY_USERS="user1(password1),user2(password2)
touch /etc/nginx/restrictions
touch /etc/nginx/apirestrictions
touch /etc/nginx/.htpasswd

if [[ ! -z $GATEWAY_ALLOWED_IPS ]] || [[ ! -z $GATEWAY_DENIED_IPS ]] || [[ ! -z $GATEWAY_USERS ]]; then
    for ip_allowed in $(echo $GATEWAY_ALLOWED_IPS | sed "s/,/ /g")
        do
            echo "allow $ip_allowed;" >> /etc/nginx/restrictions
        done
    for ip_denied in $(echo $GATEWAY_DENIED_IPS | sed "s/,/ /g")
        do
            echo "deny $ip_denied;" >> /etc/nginx/restrictions
        done
    if [[ ! -z $GATEWAY_USERS ]]; then
         for user in $(echo $GATEWAY_USERS | sed "s/,/ /g")
            do
               login=$(echo $user | cut -d ':' -f 1)
               passwd=$(echo $user | cut -d ':' -f 2)
               htpasswd -nbB -C 12 $login $passwd  >> /etc/nginx/.htpasswd 
            done
        cat basic_auth.conf.sample >> /etc/nginx/restrictions  
    fi
    if [[ -z $GATEWAY_DENIED_IPS ]] && [[ ! -z $GATEWAY_ALLOWED_IPS ]]; then
            echo "deny all;" >> /etc/nginx/restrictions
    fi
fi

if [[ ! -z $GLOBAL_REQUEST_LIMIT_ACTIVATE ]] &&  [[ "$GLOBAL_REQUEST_LIMIT_ACTIVATE" = "1" ]]; then
  echo "limit_req_zone \$binary_remote_addr zone=globaliplimits:$GLOBAL_REQUEST_LIMIT_MEMORY rate=$GLOBAL_REQUEST_LIMIT_RATE;" >> /etc/nginx/conf.d/limits.conf
  if [[ ! -z $GLOBAL_REQUEST_LIMIT_BURST ]]; then
    if [ "$GLOBAL_REQUEST_LIMIT_NODELAY" = "on" ] || [ "$GLOBAL_REQUEST_LIMIT_NODELAY" = "1" ];then
      export GLOBAL_REQUEST_LIMITS="limit_req zone=globaliplimits burst=$GLOBAL_REQUEST_LIMIT_BURST nodelay;"     
    else
      export GLOBAL_REQUEST_LIMITS="limit_req zone=globaliplimits burst=$GLOBAL_REQUEST_LIMIT_BURST;"
    fi  
  else
    export GLOBAL_REQUEST_LIMITS="limit_req zone=globaliplimits;" 
  fi
  echo $GLOBAL_REQUEST_LIMITS >> /etc/nginx/restrictions 
fi

if [[ ! -z $API_REQUEST_LIMIT_ACTIVATE ]] &&  [[ "$API_REQUEST_LIMIT_ACTIVATE" = "1" ]]; then
  echo "limit_req_zone \$binary_remote_addr zone=apiiplimits:$API_REQUEST_LIMIT_MEMORY rate=$API_REQUEST_LIMIT_RATE;" >> /etc/nginx/conf.d/limits.conf
  if [[ ! -z $API_REQUEST_LIMIT_BURST ]]; then
    if [ "$API_REQUEST_LIMIT_NODELAY" = "on" ] || [ "$API_REQUEST_LIMIT_NODELAY" = "1" ];then
      export API_REQUEST_LIMITS="limit_req zone=apiiplimits burst=$API_REQUEST_LIMIT_BURST nodelay;"
    else
      export API_REQUEST_LIMITS="limit_req zone=apiiplimits burst=$API_REQUEST_LIMIT_BURST;"        
    fi
  else
    export API_REQUEST_LIMITS="limit_req zone=apiiplimits;"                                  
  fi
    echo $API_REQUEST_LIMITS >> /etc/nginx/apirestrictions
fi

unset GATEWAY_USERS
unset GATEWAY_DENIED_IPS
unset GATEWAY_ALLOWED_IPS
exec "$@"
