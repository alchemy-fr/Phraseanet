#bin/bash
## todo remove the current trusted proxies   

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet Trusted Proxies"
    counter=0 
    if [[ -n $PHRASEANET_TRUSTED_PROXIES ]]; then
        for i in $(echo $PHRASEANET_TRUSTED_PROXIES | sed "s/,/ /g")
            do
                counter=$(( counter+1 ))
                if [[ $counter -eq 1 ]] ; then
                    bin/setup system:config set trusted-proxies $i
                    bin/setup system:config add trusted-proxies $i
                else
                    bin/setup system:config add trusted-proxies $i   
                fi
            done
    fi

## todo remove the currrent DEBUG_ALLOWED_IP

    echo `date +"%Y-%m-%d %H:%M:%S"` " - Applying infrastructure stack setup to Phraseanet debugger allowed IP"
    counter=0 
    if [[ -n $PHRASEANET_DEBUG_ALLOWED_IP ]]; then
        for i in $(echo $PHRASEANET_DEBUG_ALLOWED_IP | sed "s/,/ /g")
            do
                counter=$(( counter+1 ))
                if [[ $counter -eq 1 ]] ; then
                    bin/setup system:config set debugger.allowed-ips $i
                    bin/setup system:config add debugger.allowed-ips $i
                else
                    bin/setup system:config add debugger.allowed-ips $i   
                fi
            done
    fi

