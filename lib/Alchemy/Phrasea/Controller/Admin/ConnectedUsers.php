<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConnectedUsers implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app, Request $request) {

                $app['twig']->addFilter('AppName', new \Twig_Filter_Function(__CLASS__ . '::appName'));

                return new Response(
                        $app['twig']->render(
                            'admin/connected-users.html.twig', array('datas' => \Session_Handler::get_active_sessions()
                            )
                        )
                );
            });

        return $controllers;
    }

    public function appName($appId)
    {
        $appRef = array(
            '0' => _('admin::monitor: module inconnu')
            , '1' => _('admin::monitor: module production')
            , '2' => _('admin::monitor: module client')
            , '3' => _('admin::monitor: module admin')
            , '4' => _('admin::monitor: module report')
            , '5' => _('admin::monitor: module thesaurus')
            , '6' => _('admin::monitor: module comparateur')
            , '7' => _('admin::monitor: module validation')
            , '8' => _('admin::monitor: module upload')
        );

        return isset($appRef[$appId]) ? $appRef[$appId] : $appRef['0'];
    }
}
