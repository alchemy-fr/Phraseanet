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
class Tasks implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /*
         * route /admin/tasks/
         *  tasks status in json
         * or
         *  task manager page in html
         */
        $controllers->get('/', function(Application $app, Request $request) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
                $task_manager = new \task_manager($appbox);

                if ($request->getContentType() == 'json') {

                    return $app->json($task_manager->toArray());
                } else {

                    $template = 'admin/tasks/list.html.twig';
                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    return $twig->render($template, array(
                            'task_manager'  => $task_manager,
                            'scheduler_key' => \phrasea::scheduler_key()
                        ));
                }
            });

            /**
             * route /admin/tasks/create
             */
        $controllers->post('/create/', function(Application $app, Request $request) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

                $tcl = $request->get('tcl');
                if( $tcl )
                {
                    $task = \task_abstract::create($appbox, $tcl);
                    $tid = $task->getId();

                    return $app->redirect('/admin/task/'.$tid);
                    // return $tid;
                }

                return $app->redirect('/admin/publications/list/');
            });

        return $controllers;
    }
}
