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
use Symfony\Component\Finder\Finder;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Task implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = \appbox::get_instance($app['phraseanet.core']);

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/task/{id}/log
         *  show logs of a task
         */
        $controllers->get('/{id}/log', function(Application $app, Request $request, $id) use ($appbox) {
                $registry = $appbox->get_registry();
                $logdir = \p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

                $rname = '/task_' . $id . '((\.log)|(-.*\.log))$/';

                $finder = new Finder();
                $finder
                    ->files()->name($rname)
                    ->in($logdir)
                    //                   ->date('> now - 1 days')
                    ->sortByModifiedTime();

                $found = false;
                foreach ($finder->getIterator() as $file) {
                    // printf("%s <br/>\n", ($file->getRealPath()));
                    if ($request->get('clr') == $file->getFilename()) {
                        file_put_contents($file->getRealPath(), '');
                        $found = true;
                    }
                }
                if ($found) {
                    return $app->redirect(sprintf("/admin/task/%s/log", urlencode($id)));
                }

                return $app->stream(
                        function() use ($finder, $id) {
                            foreach ($finder->getIterator() as $file) {
                                printf("<h4>%s\n", $file->getRealPath());
                                printf("&nbsp;<a href=\"/admin/task/%s/log?clr=%s\">%s</a>"
                                    , $id
                                    , urlencode($file->getFilename())
                                    , _('Clear')
                                );
                                print("</h4>\n<pre>\n");
                                print(htmlentities(file_get_contents($file->getRealPath())));
                                print("</pre>\n");

                                ob_flush();
                                flush();
                            }
                        });
            });

        /*
         * route /admin/task/{id}/delete
         *  delete a task
         */
        $controllers->get('/{id}/delete', function(Application $app, Request $request, $id) use ($appbox) {
                $task_manager = new \task_manager($appbox);

                try {
                    $task = $task_manager->getTask($id);
                    $task->delete();

                    return $app->redirect('/admin/tasks/');
                } catch (\Exception $e) {

                    /*
                     * todo : add a message back
                     */
                    return $app->redirect('/admin/tasks/');
                }
            });

        /*
         * route /admin/task/{id}/start
         *  set a task to 'tostart'
         */
        $controllers->get('/{id}/tostart', function(Application $app, Request $request, $id) use ($appbox) {
                $task_manager = new \task_manager($appbox);

                $ret = false;
                try {
                    $task = $task_manager->getTask($id);
                    $pid = (int) ($task->getPID());
                    if ( ! $pid) {
                        $task->setState(\task_abstract::STATE_TOSTART);
                        $ret = true;
                    }
                } catch (Exception $e) {

                }

                return $app->json($ret);
            });

        /*
         * route /admin/task/{id}/stop
         *  set a task to 'tostop'
         */
        $controllers->get('/{id}/tostop', function(Application $app, Request $request, $id) use ($appbox) {
                $task_manager = new \task_manager($appbox);

                $ret = false;
                try {
                    $task = $task_manager->getTask($id);
                    $pid = $task->getPID();
                    $signal = $request->get('signal');
                    $task->setState(\task_abstract::STATE_TOSTOP);

                    if ((int) $pid > 0 && (int) $signal > 0 && function_exists('posix_kill')) {
                        posix_kill((int) $pid, (int) $signal);
                    }

                    $ret = true;
                } catch (Exception $e) {

                }

                return $app->json($ret);
            });

        /*
         * route /admin/task/{id}/resetcrashcounter
         * return json
         */
        $controllers->get('/{id}/resetcrashcounter', function(Application $app, Request $request, $id) use ($appbox) {
                $task_manager = new \task_manager($appbox);

                try {
                    $task = $task_manager->getTask($id);

                    $task->resetCrashCounter();

                    return $app->json(true);
                } catch (\Exception $e) {

                    return $app->json(false);
                }
            });

        /*
         * route /admin/task/{id}
         *  render a task editing interface
         */
        $controllers->get('/{id}', function(Application $app, Request $request, $id) use ($appbox) {

                $task_manager = new \task_manager($appbox);
                $task = $task_manager->getTask($id);

                /* @var $twig \Twig_Environment */
                $twig = $app['phraseanet.core']->getTwig();
                $template = 'admin/task.html';
                return $twig->render($template, array(
                        'task' => $task,
                        'view' => 'XML'
                    ));
            });

        return $controllers;
    }
}
