# openid configuration

#### phraseanet configuration
To connect with an openid with phraseanet, add the following config in the configuration.yml file


```yaml
authentication:
    providers:
        openid-1:
            enabled: true
            display: true
            title: ' openid 1'
            type: openid
            options:
                client-id: 'client-id'
                client-secret: 'client-secret'
                base-url: 'https://keycloak.phrasea.local'
                realm-name: phrasea
                # if true, can only connect with this provider
                # the user cannot connect with the default phraseanet login form
                exclusive: false
                icon-uri: null
                birth-group: _firstlog
                everyone-group: _everyone
                metamodel: _metamodel
                # group model prefix
                model-gpfx: _M_  
                # user model prefix
                model-upfx: _U_
                debug: false
                # logout with phraseanet and also logout with keycloak
                auto-logout: true  
                auto-connect-idp-name: null
                
```


#### keycloak configuration

- create a new client
- get clien-id and client-secret
- in the client setting:
   
   set the 'Valid redirect URIs' field with `https://{phraseanet-host}/login/provider/{provider-name}/callback/`
   eg: https://phraseanet.phrasea.local/login/provider/openid-1/callback/
     
   set the 'Valid post logout redirect URIs' field with `https://{phraseanet-host}/login/logout/` eg: https://phraseanet.phrasea.local/login/logout/

- Choose a client > client scopes >  '.... dedicated'
  
  add a 'groups' mapper if not exist,  > Add mapper > by configuration  
  
  `Mapper type` => Group Membership  
  `Name` => groups  
  `Token Claim Name` => groups  
  `Full group path`  => off   
  `Add to userinfo`  => on
