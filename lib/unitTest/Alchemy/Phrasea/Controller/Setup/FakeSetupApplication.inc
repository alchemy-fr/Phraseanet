<?php

namespace Alchemy\Phrasea\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Controller\Setup as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

require_once __DIR__ . '/../../../../../bootstrap.php';

return call_user_func(function()
    {
      $app = new \Silex\Application();

      $app['Core'] = \bootstrap::getCore();

      $app['install'] = true;

      $app->get('/', function() use ($app)
        {
          return $app->redirect('/setup/installer/');
        });

      $app->mount('/installer/', new Controller\Installer());
      $app->mount('/test', new ControllerUtils\PathFileTest());
      $app->mount('/connection_test', new ControllerUtils\ConnectionTest());

      return $app;
    });
