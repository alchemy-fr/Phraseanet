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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Scheduler implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = \appbox::get_instance($app['Core']);
        $session = $appbox->get_session();

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/tasks/
         *  tasks status in json
         * or
         *  task manager page in html
         */
        $controllers->get('/', function(Application $app, Request $request) use ($app) {
                $request = $app['request'];
                $task_manager = new \task_manager($appbox);

                return "";
            });

        return $controllers;
    }
}
