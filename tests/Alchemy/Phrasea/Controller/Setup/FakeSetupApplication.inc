<?php

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Setup as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

return call_user_func(function() {
            $app = new PhraseaApplication('test');

            $app['install'] = true;

            $app->get('/', function() use ($app) {
                return $app->redirect('/setup/installer/');
            });

            $app->mount('/installer/', new Controller\Installer());
            $app->mount('/test', new ControllerUtils\PathFileTest());
            $app->mount('/connection_test', new ControllerUtils\ConnectionTest());


            $app->error(function($e) {

                });

            return $app;
        });
