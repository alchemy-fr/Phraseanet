Phraseanet 4.1 - Digital Asset Management application
=====================================================

[![CircleCI](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/master.svg?style=shield)](https://circleci.com/gh/alchemy-fr/Phraseanet/tree/master)

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

# Development :

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


# Docker build

WARNING : still in a work-in-progress status and can be used only for test purposes.

The docker distribution come with 3 differents containers :
* An nginx that act as the front http server.
* The php-fpm who serves the php files through nginx.
* The worker who execute Phraseanet scheduler.

## How to build

You can build all the images with the following command at the root directory, choosing an arbirary TAG name :

    ./build.sh <TAG>

It will build and tag the following images :

    local/phraseanet-worker:<TAG>
    local/phraseanet-fpm:<TAG>
    local/phraseanet-nginx:<TAG>

# Deploy the application

Once the images are built, you can deploy the entire phraseanet stack using the repository : https://github.com/alchemy-fr/phraseanet-docker and follow the instruction inside its `README.md` file.
