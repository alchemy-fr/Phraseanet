Alchemy Embed Bundle / Development workflow
===========================================

Context: 
Phraseanet deployed with docker-compose with `docker-compose.override.yml`

#####  Get and install the lib embed-bundle for development

Change the directory to the phraseanet workspace

Clone the library embed-bundle

`git clone https://github.com/alchemy-fr/embed-bundle.git`

Run the developer shell on the Phraseanet (builder container)

`docker-compose run --rm builder /bin/bash`

Change directory to embed-bundle folder

`cd embed-bundle`

Install the lib

`npm install`

Make your modification and generate dist when finished

`npm run dist`

or use `npm run dev` to watch during development

The dist directory is to be commited after development.

##### Synchronise the embed-bundle folder with phraseanet for local testing (do not commit)

Change directory to phraseanet `cd ..`

Remove the actual alchemy/embed-bundle in phraseanet

`composer remove alchemy/embed-bundle`

On composer.json of phraseanet, change the repositories information

```
{
    "type": "vcs",
    "url": "https://github.com/alchemy-fr/embed-bundle.git"
}
```

by
```
{
      "type": "path",
      "url": "/var/alchemy/Phraseanet/embed-bundle",
      "options": {
        "versions": {
          "alchemy/embed-bundle": "4.2-dev" // the number version you want prefix with -dev
        }
      }
    }
```

and add embed-bundle from the local embed-bundle folder

`composer require "alchemy/embed-bundle 4.2-dev"`

Run `make install_assets` to copy assets from embed-bundle dist to phraseanet

##### When development finished
 Commit only the modification from embed-bundle directory with the dist folder
 
 Release a new version of embed-bundle
 
 Reinitialize all modification on `composer.json` during development and
 
 Run `composer update "alchemy/embed-bundle"` to update embed-bundle to the new version

 Commit and push `composer.lock` on phraseanet
 
