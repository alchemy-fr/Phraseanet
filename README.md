Phraseanet 4.0 - Digital Asset Management application
=====================================================

[![CircleCI](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/4.0.svg?style=shield)](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/4.0)

# Features :

 - Metadata Management (include Thesaurus and DublinCore Mapping)
 - RestFull APIS
 - Elasticsearch search engine
 - Multiple resolution assets generation


# License :

Phraseanet is licensed under GPL-v3 license.

# Documentation :

https://docs.phraseanet.com/

# Installation :

You **must** not download the source from GitHub, but download a packaged version here :

https://www.phraseanet.com/download/

And follow the install steps described at https://docs.phraseanet.com/4.0/en/Admin/Install.html

# Try Phraseanet :

You can also download a testing pre installed Virtual Machine in OVA format here :

https://www.phraseanet.com/download/

# With Docker

## Prerequisites

- docker-compose
- docker >=v18.01-ce

## Get started

You should review the default env variables defined in `.env` file.
Use `export` to override these values.

i.e:
```bash
export PHRASEANET_DOCKER_TAG=latest
export INSTALL_ACCOUNT_EMAIL=foo@bar.com
export INSTALL_ACCOUNT_PASSWORD=$3cr3t!
export PHRASEANET_APP_PORT=8082
```

### Using a env.local (custom .env)

It may be easier to deal with a local file to manage our env variables.

You can add your `env.local` at the root of this project and define a command alias in your `~/.bashrc`:

```bash
alias dc="env $(cat env.local | grep -v '#' | tr '\n' ' ') docker-compose"
```

### Running the application

If you are not interested in the development of Phraseanet, you can ignore everything in `.env` after the `DEV Purpose` part.

    docker-compose -f docker-compose.yml up -d

Why this option `-f docker-compose.yml`?
The development and integration concerns are separated using a `docker-compose.override.yml`. By default, `docker-compose` will include this files if it exists.
If you don't work on phraseanet development, avoiding this `-f docker-compose.yml` parameters will throw errors. So you have to add this options on every `docker-compose` commands to avoid this inclusion.

> You can also delete the `docker-compose.override.yml` to get free from this behavior.

#### Running workers

```bash
docker-compose -f docker-compose.yml run --rm worker <command>
```

Where `<command>` can be:
- `bin/console task-manager:scheduler:run` (default)
- `bin/console worker:execute -m 2`
- ...


The default parameters allow you to reach the app with : `http://localhost:8082`

## Development mode

The development mode uses the `docker-compose-override.yml` file.

You can run it with:

    docker-compose up -d

The environment is not ready yet: you have to fetch all dependencies.

This can be made easily from the builder container:

    docker-compose run --rm -u app builder make install install_composer_dev

> Please note that the phraseanet image does not contain nor `composer` neither `node` tools. This allow the final image to be slim.
> If you need to use dev tools, ensure you are running the `builder` image!

### Using Xdebug

Xdebug is enabled by default with the `docker-compose.override.yml`
You can disable it by setting:

```bash
export XDEBUG_ENABLED=0
```

Remote host is fixed because of the subnet network from compose.

You need to configure file mapping in your IDE.
For PhpStorm, you can follow this example:

![PhpStorm mapping](https://i.ibb.co/GMb43Cv/image.png)

> Configure the `Absolute path on the server` to `/var/alchemy/Phraseanet` at the project root path (i.e. `~/projects/Phraseanet`).

#### Xdebug on MacOS

You have to set the following env:
```bash
XDEBUG_REMOTE_HOST=host.docker.internal
```

> Don't forget to recreate your container (`docker-compose up -d phraseanet`)

# With Vagrant (deprecated)

## Development :

For development purpose Phraseanet is shipped with ready to use development environments using vagrant.
You can easily choose betweeen a complete build or a prebuild box, with a specific PHP version.

    git clone
    vagrant up --provision

then, a prompt allow you to choose PHP version, and another one to choose a complete build or an Alchemy prebuilt boxes.

Ex:
- vagrant up --provision  //// 5.6 ///// 1  >> Build an ubuntu/xenial box with php5.6
- vagrant up --provision  //// 7.0 ///// 1  >> Build an ubuntu/xenial with php7.0
- vagrant up --provision  //// 7.2 ///// 2  >> Build the alchemy/phraseanet-php-7.2 box
- vagrant up --provision  //// 5.6 ///// 1  >> Build the alchemy/phraseanet-php-5.6 box


For development with Phraseanet API see https://docs.phraseanet.com/4.0/en/Devel/index.html
