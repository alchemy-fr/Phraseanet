<?php

namespace Alchemy\Tests\Phrasea\Controller\Setup;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Setup\Installer;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;

return call_user_func(function() {
    $app = new PhraseaApplication('test');

    $app['install'] = true;

    $app->get('/', function() use ($app) {
        return $app->redirect('/setup/installer/');
    });

    $app->mount('/installer/', new Installer());
    $app->mount('/test', new PathFileTest());
    $app->mount('/connection_test', new ConnectionTest());

    $app->error(function($e) {

    });

    return $app;
});
