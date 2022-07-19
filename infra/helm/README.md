# Helm Chart

## Deploy chart

First you should write your own configuration file named `myvalues.yaml` (see [sample.yaml](./sample.yaml))

```bash
helm install -f myvalues.yaml phraseanet ./all
```

# In case of private registry on minikube

configure first the registry credential addons

```bash
minikube addons configure registry-creds
minikube addons enable registry-creds
```

exemple : 

```bash
$ minikube addons configure registry-creds

Do you want to enable AWS Elastic Container Registry? [y/n]: y
-- Enter AWS Access Key ID: <put_access_key_here>
-- Enter AWS Secret Access Key: <put_secret_access_key_here>
-- (Optional) Enter AWS Session Token:
-- Enter AWS Region: us-west-2
-- Enter 12 digit AWS Account ID (Comma separated list): <account_number>
-- (Optional) Enter ARN of AWS role to assume:

Do you want to enable Google Container Registry? [y/n]: n

Do you want to enable Docker Registry? [y/n]: n

Do you want to enable Azure Container Registry? [y/n]: n
✅  registry-creds was successfully configured

```

enable image pull secret and give the name of your secretname into values.yml

```bash
  imagepullsecrets: "true"
  secretename: "awsecr-cred" 
```

