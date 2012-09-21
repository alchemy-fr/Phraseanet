<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Setup;

use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Upgrader implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use ($app) {
                require_once __DIR__ . '/../../../../bootstrap.php';
                $upgrade_status = \Setup_Upgrade::get_status();

                return $app['twig']->render(
                        '/setup/upgrader.html.twig'
                        , array(
                        'locale'            => $app['locale']
                        , 'upgrade_status'    => $upgrade_status
                        , 'available_locales' => $app->getAvailableLanguages()
                        , 'bad_users'         => \User_Adapter::get_wrong_email_users($app)
                        , 'version_number'    => $app['phraseanet.version']->getNumber()
                        , 'version_name'      => $app['phraseanet.version']->getName()
                        )
                );
            });

        $controllers->get('/status/', function(Application $app) {
                require_once __DIR__ . '/../../../../bootstrap.php';

                return $app->json(\Setup_Upgrade::get_status());
            });

        $controllers->post('/execute/', function(Application $app) {
                require_once __DIR__ . '/../../../../bootstrap.php';
                set_time_limit(0);
                session_write_close();
                ignore_user_abort(true);

                $appbox = $app['phraseanet.appbox'];
                $upgrader = new \Setup_Upgrade($app);
                $appbox->forceUpgrade($upgrader, $app);

                /**
                 * @todo Show recomandation instead of redirect
                 */
                return $app->redirect('/');
            });

        return $controllers;
    }
}
