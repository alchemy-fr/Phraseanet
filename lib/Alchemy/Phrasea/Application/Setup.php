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

use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Setup\Installer;
use Alchemy\Phrasea\Controller\Setup\Upgrader;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;

return call_user_func(function($environment = null) {

        $app = new PhraseaApplication();

        $app['install'] = false;
        $app['upgrade'] = false;

        $app->before(function($a) use ($app) {
            if (\setup::is_installed()) {
                if (!$app['phraseanet.appbox']->need_major_upgrade()) {
                    throw new \Exception_Setup_PhraseaAlreadyInstalled();
                }

                $app['upgrade'] = true;
            } elseif (\setup::needUpgradeConfigurationFile()) {

                if (\setup::requireGVUpgrade()) {
                    setup::upgradeGV($app['phraseanet.core']['Registry']);
                }

                $connexionInc = new \SplFileInfo(__DIR__ . '/../../../../config/connexion.inc');
                $configInc = new \SplFileInfo(__DIR__ . '/../../../../config/config.inc');

                $configuration = Configuration::build();
                $configuration->upgradeFromOldConf($configInc, $connexionInc);

                $app['install'] = true;
            } else {
                $app['install'] = true;
            }

            return;
        });

        $app->get('/', function() use ($app) {
            if ($app['install'] === true) {
                return $app->redirect('/setup/installer/');
            }if ($app['upgrade'] === true) {
                return $app->redirect('/setup/upgrader/');
            }
        });

        $app->mount('/installer/', new Installer());
        $app->mount('/upgrader/', new Upgrader());
        $app->mount('/test', new PathFileTest());
        $app->mount('/connection_test', new ConnectionTest());

        $app->error(function($e) use ($app) {
            if ($e instanceof \Exception_Setup_PhraseaAlreadyInstalled) {
                return $app->redirect('/login/');
            }

            return new Response('Internal Server Error', 500, array('X-Status-Code' => 500));
        });

        return $app;
    }, isset($environment) ? $environment : null
);
