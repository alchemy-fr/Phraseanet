<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Controller\Setup as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

require_once __DIR__ . '/../../../bootstrap.php';
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function()
    {
      $app = new \Silex\Application();

      $app['Core'] = \bootstrap::getCore();

      $app['install'] = false;
      $app['upgrade'] = false;

      $app->before(function($a) use ($app)
        {
          if (\setup::is_installed())
          {
            $appbox = \appbox::get_instance($app['Core']);

            if (!$appbox->need_major_upgrade())
            {
              throw new \Exception_Setup_PhraseaAlreadyInstalled();
            }

            $app['upgrade'] = true;
          }
          elseif (\setup::needUpgradeConfigurationFile())
          {
            $connexionInc  = new \SplFileObject(__DIR__ . '/../../../../config/connexion.inc');
            $configInc = new \SplFileObject(__DIR__ . '/../../../../config/config.inc');

            $configuration = \Alchemy\Phrasea\Core\Configuration::build();
            $configuration->upgradeFromOldConf($configInc, $connexionInc);

            $app['install'] = true;
          }
          else
          {
            $app['install'] = true;
          }

          return;
        });


      $app->get('/', function() use ($app)
        {
          if ($app['install'] === true)
            return $app->redirect('/setup/installer/');
          if ($app['upgrade'] === true)
            return $app->redirect('/setup/upgrader/');
        });


      $app->mount('/installer/', new Controller\Installer());
      $app->mount('/upgrader/', new Controller\Upgrader());
      $app->mount('/test', new ControllerUtils\PathFileTest());
      $app->mount('/connection_test', new ControllerUtils\ConnectionTest());

      $app->error(function($e) use ($app)
        {
          if ($e instanceof \Exception_Setup_PhraseaAlreadyInstalled)
          {
            return $app->redirect('/login/');
          }

          return new Response('Internal Server Error', 500);
        });

      return $app;
    });
