# UPGRADE FROM 3.7 TO 3.8

Here are some release notes about upgrading from Phraseanet 3.7 and Phraseanet 3.8.

Phraseanet 3.8 is a new step in moving Phraseanet to a more decoupled design. We did
a lot of cleanup and now delegate some behavior to dedicated components. this brings
some new features, robustness and stability.

These enhancements are described in the CHANGELOG file. The purpose of this document
is to provide a list a BC breaks / Changes.

## Configuration :

Configuration has been drastically simplified. There is now one file to
configure Phraseanet : `config/configuration.yml`.

This file is now compiled to plain PHP for best performances. If you ever edit
this file manually, please run the `bin/console compile:configuration` command
to re-compile the configuration.

## Nginx :

If you are using Nginx as Phraseanet web-server, you must update you virtual-host
configuration as follow :

    ```
    server {
        listen       80;
        server_name  yourdomain.tld;
        root         /var/www/Phraseanet/www;

        index        index.php;

        location /api {
            rewrite ^(.*)$ /api.php/$1 last;
        }

        location / {
            # try to serve file directly, fallback to rewrite
            try_files $uri $uri/ @rewriteapp;
        }

        location @rewriteapp {
            rewrite ^(.*)$ /index.php/$1 last;
        }

        # PHP scripts -> PHP-FPM server listening on 127.0.0.1:9000
        location ~ ^/(index|index_dev|api)\.php(/|$) {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }
    }
    ```

## Console

Phraseanet 3.8 comes with a new command-line utility : `bin/setup`. This utility
brings commands that can be run when Phraseanet is not installed.

It introduces 3 BC breaks :

    - `bin/console system:upgrade` is replaced by `bin/setup system:upgrade`
    - `bin/console check:system` is replaced by `bin/setup check:system`
    - `bin/console check:config` has been dropped

The idea of `bin/setup` is to provide an commandline tool that is not aware of
Phraseanet Installation, whereas `bin/console` requires an up-to-date Phraseanet
install.

## Database Upgrade

Database will be upgraded when running the `bin/console system:upgrade` command.
This command will not remove old tables. To remove them, use the
`--dump` option of the previous command to get a dump of the raw SQL commands to
execute;

## Customization

If you were using custom homepage or LDAP connection, they might not work.
Please disable it before upgrading.
