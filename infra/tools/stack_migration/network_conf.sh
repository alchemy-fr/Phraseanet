#bin/bash

cd "/var/alchemy/Phraseanet"
## todo remove the current trusted proxies   

echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet Trusted Proxies"
counter=0 
if [[ -n $PHRASEANET_TRUSTED_PROXIES ]]; then
    for i in $(echo $PHRASEANET_TRUSTED_PROXIES | sed "s/,/ /g")
        do
            counter=$(( counter+1 ))
            if [[ $counter -eq 1 ]] ; then
                bin/setup system:config -s set trusted-proxies $i
                bin/setup system:config -s add trusted-proxies $i
            else
                bin/setup system:config -s add trusted-proxies $i   
            fi
        done
fi

echo `date +"%Y-%m-%d %H:%M:%S"` " - Setup of Phraseanet Trusted Proxies applied"

## todo remove the currrent DEBUG_ALLOWED_IP

echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet debugger allowed IP"
counter=0 
if [[ -n $PHRASEANET_DEBUG_ALLOWED_IP ]]; then
    for i in $(echo $PHRASEANET_DEBUG_ALLOWED_IP | sed "s/,/ /g")
        do
            counter=$(( counter+1 ))
            if [[ $counter -eq 1 ]] ; then
                bin/setup system:config -s set debugger.allowed-ips $i
                bin/setup system:config -s add debugger.allowed-ips $i
            else
                bin/setup system:config -s add debugger.allowed-ips $i   
            fi
        done
fi
echo `date +"%Y-%m-%d %H:%M:%S"` " - Setup of Phraseanet debugger allowed IP applied"

cd -
