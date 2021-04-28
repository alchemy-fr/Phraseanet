# How to update the documentation in swaggerhub :

The doc is composed of 3 files
- `api.yaml`  (main file)
- `responses.yaml`
- `schemas.yaml`

to update in swaggerhub (single file) :
- install swagger-cli   
  
    `sudo npm install -g swagger-cli`


- compile sources in a single file for swaggerhub (run from <phraseanet-dir>)

    `swagger-cli bundle API_documentation/v3/api.yaml -r -o API_documentation/v3/_compiled.yaml -t yaml`


- copy/paste the generated content from `_compiled.yaml` to
  
    https://app.swaggerhub.com/apis/alchemy-fr/phraseanet.api.v3/1.0.0-oas3
