Phraseanet 4.1 - Digital Asset Management application
=====================================================

[![CircleCI](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/master.svg?style=shield)](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/master)

# Features :

 - Metadata Management (include Thesaurus and DublinCore Mapping)
 - RestFull APIS
 - Elasticsearch search engine
 - Multiple resolution assets generation

# Documentation :

https://docs.phraseanet.com/

# Installation :

You **must** not download the source from GitHub, but download a packaged version here :

https://www.phraseanet.com/download/

And follow the install steps described at https://docs.phraseanet.com/4.0/en/Admin/Install.html

# Try Phraseanet :

You can also download a testing pre installed Virtual Machine in OVA format here :

https://www.phraseanet.com/download/

# Development :

For development purpose Phraseanet is shipped with ready to use development environments using vagrant.

- git clone
- vagrant up


For development with Phraseanet API see https://docs.phraseanet.com/4.0/en/Devel/index.html

# License :

Phraseanet is licensed under GPL-v3 license.


# Docker build

WARNING : still in a work-in-progress status and can be used only for test purposes.

The docker distribution come with 2 differents containers :
* an nginx that act as the front http server.
* the php-fpm who serves the php files through nginx.

## How to build

The two images can be built respectively with these two commands :

    # nginx server
    docker build --target phraseanet-nginx -t phraseanet-nginx .

    # php-fpm application
    docker build --target phraseanet -t phraseanet .


