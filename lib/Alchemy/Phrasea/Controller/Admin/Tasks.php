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
        $appbox = \appbox::get_instance($app['Core']);
        $session = $appbox->get_session();

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/tasks/
         *  tasks status in json
         * or
         *  task manager page in html
         */
        $controllers->get('/', function(Application $app, Request $request) use ($appbox) {
                $task_manager = new \task_manager($appbox);

                if ($request->getContentType() == 'json') {

                    return $app->json($task_manager->toArray());
                } else {

                    $template = 'admin/tasks/list.html.twig';
                    /* @var $twig \Twig_Environment */
                    $twig = $app['Core']->getTwig();

                    return $twig->render($template, array(
                            'task_manager'  => $task_manager,
                            'scheduler_key' => \phrasea::scheduler_key()
                        ));
                }
            });

        /*
          $controllers->post('/create/', function() use ($app, $appbox) {

          $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
          $request = $app['request'];

          $feed = \Feed_Adapter::create($appbox, $user, $request->get('title'), $request->get('subtitle'));

          if ($request->get('public') == '1')
          $feed->set_public(true);
          elseif ($request->get('base_id'))
          $feed->set_collection(\collection::get_from_base_id($request->get('base_id')));

          return $app->redirect('/admin/publications/list/');
          });
         */

        return $controllers;
    }
}
