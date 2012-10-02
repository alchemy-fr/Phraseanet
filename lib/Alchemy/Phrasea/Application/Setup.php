<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Setup\Installer;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;

return call_user_func(function() {

    $app = new Application();

    $app->get('/', function(PhraseaApplication $app) {
        if (!$app['phraseanet.configuration-tester']->isBlank()) {
            return $app->redirect('/login/');
        }

        return $app->redirect('/setup/installer/');
    });

    $app->mount('/installer/', new Installer());
    $app->mount('/test', new PathFileTest());
    $app->mount('/connection_test', new ConnectionTest());

    $app->error(function($e) use ($app) {
            if ($e instanceof \Exception_Setup_PhraseaAlreadyInstalled) {
                return $app->redirect('/login/');
            }

            return new Response('Internal Server Error', 500, array('X-Status-Code' => 500));
        });

    return $app;
});
