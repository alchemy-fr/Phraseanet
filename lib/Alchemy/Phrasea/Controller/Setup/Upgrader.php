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

use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

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

                /* @var $twig \Twig_Environment */
                $twig = $app['phraseanet.core']->getTwig();

                $html = $twig->render(
                    '/setup/upgrader.html.twig'
                    , array(
                    'locale'            => \Session_Handler::get_locale()
                    , 'upgrade_status'    => $upgrade_status
                    , 'available_locales' => $app['phraseanet.core']::getAvailableLanguages()
                    , 'bad_users'         => \User_Adapter::get_wrong_email_users($app['phraseanet.appbox'])
                    , 'version_number'    => $app['phraseanet.core']['Version']->getNumber()
                    , 'version_name'      => $app['phraseanet.core']['Version']->getName()
                    )
                );
                ini_set('display_errors', 'on');

                return new Response($html);
            });

        $controllers->get('/status/', function() use ($app) {
                require_once __DIR__ . '/../../../../bootstrap.php';

                $datas = \Setup_Upgrade::get_status();

                $Serializer = $app['phraseanet.core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('Content-Type: application/json')
                );
            });

        $controllers->post('/execute/', function() use ($app) {
                require_once __DIR__ . '/../../../../bootstrap.php';
                set_time_limit(0);
                session_write_close();
                ignore_user_abort(true);

                $appbox = $app['phraseanet.appbox'];
                $upgrader = new \Setup_Upgrade($appbox);
                $appbox->forceUpgrade($upgrader);

                /**
                 * @todo Show recomandation instead of redirect
                 */

                return new \Symfony\Component\HttpFoundation\RedirectResponse('/');
            });

        return $controllers;
    }
}
