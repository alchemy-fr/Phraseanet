<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

class TaskManager implements ControllerProviderInterface
{


    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app, Request $request) {

                    return $app->redirect('/admin/task-manager/tasks/');
            });



        /*
         * route /admin/task-manager/tasks/
         *  tasks status in json
         * or
         *  task manager page in html
         */
        $controllers->get('/tasks/', function(Application $app, Request $request) {
                $task_manager = new \task_manager($app['phraseanet.appbox']);

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
             * route /admin/task-manager/tasks/create
             */
        $controllers->post('/tasks/create/', function(Application $app, Request $request) {
                $appbox = $app['phraseanet.appbox'];
                $user = \User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);

                $tcl = $request->get('tcl');
                if( $tcl )
                {
                    $task = \task_abstract::create($appbox, $tcl);
                    $tid = $task->getId();

                    return $app->redirect('/admin/task-manager/task/'.$tid);
                    // return $tid;
                }

                return $app->redirect('/admin/task-manager/');
            });

        /*
         * route /admin/taskmanager/scheduler/start
         */
        $controllers->get('/scheduler/start', $this->call('startScheduler'));

        /*
         * route /admin/scheduler/stop
         */
        $controllers->get('/scheduler/stop', function(Application $app, Request $request) use ($app) {
                try {
                    $task_manager = new \task_manager($app['phraseanet.appbox']);

                    $task_manager->setSchedulerState(\task_manager::STATE_TOSTOP);

                    return $app->json(true);
                } catch (Exception $e) {

                }

                return $app->json(false);
            });

        $controllers->get('/scheduler/log', function(Application $app, Request $request) {
                $appbox = $app['phraseanet.appbox'];
                $registry = $appbox->get_registry();
                $logdir = \p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

                $rname = '/scheduler((\.log)|(-.*\.log))$/';

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
                    return $app->redirect("/admin/task-manager/scheduler/log");
                }

                return $app->stream(
                        function() use ($finder) {
                            foreach ($finder->getIterator() as $file) {
                                printf("<h4>%s\n", $file->getRealPath());
                                printf("&nbsp;<a href=\"/admin/task-manager/scheduler/log?clr=%s\">%s</a>"
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


        $controllers->get('/task/{id}/log', function(Application $app, Request $request, $id) {
                $appbox = $app['phraseanet.appbox'];
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
                    return $app->redirect(sprintf("/admin/task-manager/task/%s/log", urlencode($id)));
                }

                return $app->stream(
                        function() use ($finder, $id) {
                            foreach ($finder->getIterator() as $file) {
                                printf("<h4>%s\n", $file->getRealPath());
                                printf("&nbsp;<a href=\"/admin/task-manager/task/%s/log?clr=%s\">%s</a>"
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
         * route /admin/task-manager/task/{id}/delete
         *  delete a task
         */
        $controllers->get('/task/{id}/delete', function(Application $app, Request $request, $id) {

                try {
                    $task = $app['task-manager']->getTask($id);
                    $task->delete();

                    return $app->redirect('/admin/task-manager/tasks/');
                } catch (\Exception $e) {

                    /*
                     * todo : add a message back
                     */
                    return $app->redirect('/admin/task-manager/tasks/');
                }
            });

        /*
         * route /admin/task-manager/task/{id}/start
         *  set a task to 'tostart'
         */
        $controllers->get('/task/{id}/tostart', function(Application $app, Request $request, $id) {
                $task_manager = new \task_manager($app['phraseanet.appbox']);

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
         * route /admin/task-manager/task/{id}/stop
         *  set a task to 'tostop'
         */
        $controllers->get('/task/{id}/tostop', function(Application $app, Request $request, $id) {

                $task_manager = new \task_manager($app['phraseanet.appbox']);

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
         * route /admin/task-manager/task/{id}/resetcrashcounter
         * return json
         */
        $controllers->get('/task/{id}/resetcrashcounter/', function(Application $app, Request $request, $id) {
                $task_manager = new \task_manager($app['phraseanet.appbox']);

                try {
                    $task = $task_manager->getTask($id);

                    $task->resetCrashCounter();

                    return $app->json(true);
                } catch (\Exception $e) {

                    return $app->json(false);
                }
            });

        /*
         * route /admin/task-manager/task/{id}/save
         * return json
         */
        $controllers->post('/task/{id}/save/', function(Application $app, Request $request, $id) {

                $task_manager = new \task_manager($app['phraseanet.appbox']);

                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->strictErrorChecking = true;
                try {
                    if ( ! @$dom->loadXML($request->get('xml'))) {
                        throw new XMLParseErrorException($request->get('xml'));
                    }
                } catch (XMLParseErrorException $e) {
                    return new Response(
                            $e->getXMLErrMessage(),
                            412    // Precondition Failed
                    );
                }

                try {
                    $task = $task_manager->getTask($id);

                    $task->setTitle($request->get('title'));
                    $task->setActive(\p4field::isyes($request->get('active')));
                    $task->setSettings($request->get('xml'));

                    return $app->json(true);
                } catch (\Exception $e) {

                    return new Response(
                            'Bad task ID',
                            404    // Not Found
                    );
                }
            });

        /*
         * route /admin/task-manager/task/{id}/facility/
         * call callback(s) of a task, for ex. to transform gui(form) to xml settings
         */
        $controllers->post('/task/{id}/facility/', function(Application $app, Request $request, $id) {

                $task_manager = new \task_manager($app['phraseanet.appbox']);
                $ret = '';
                try {
                    $task = $task_manager->getTask($id);
                } catch (\Exception $e) {
                    return new Response(
                            'Bad task ID',
                            404    // Not Found
                    );
                }

                switch ($request->get('__action')) {
                    case 'FORM2XML':
                        if (@simplexml_load_string($request->get('__xml'))) {
                            $ret = $task->graphic2xml($request->get('__xml'));
                        } else {
                            $ret = new Response(
                                    'Bad XML',
                                    412    // Precondition Failed
                            );
                        }
                        break;
                    case null:
                        // no __action, so delegates to the task (call method "facility")
                        if (method_exists($task, 'facility')) {
                            $ret = $task->facility();
                        }
                        break;
                    default:
                        $ret = new Response(
                                'Bad action',
                                404    // Not Found
                        );
                        break;
                }
                return $ret;
            });

        /*
         * route /admin/task-manager/task/{id}
         *  render a task editing interface
         */
        $controllers->get('/task/{id}', function(Application $app, Request $request, $id) {

                $task_manager = new \task_manager($app['phraseanet.appbox']);
                $task = $task_manager->getTask($id);

                /* @var $twig \Twig_Environment */
                $twig = $app['phraseanet.core']->getTwig();
                $template = 'admin/task.html.twig';
                return $twig->render($template, array(
                        'task' => $task,
                        'view' => 'XML'
                    ));
            });

        /*
         * route /admin/task/checkxml/
         * check if the xml is valid
         */
        $controllers->post('/task/checkxml/', function(Application $app, Request $request) {
                $ret = array('ok'  => true, 'err' => null);
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->strictErrorChecking = true;
                try {
                    if ( ! @$dom->loadXML($request->get('xml'))) {
                        throw new XMLParseErrorException($request->get('xml'));
                    }
                    $ret = $app->json($ret);
                } catch (XMLParseErrorException $e) {
                    $ret = new Response(
                            $e->getXMLErrMessage(),
                            412    // Precondition Failed
                    );
                }
                return $ret;
            });

            return $controllers;
    }

    public function startScheduler(Application $app, Request $request)
    {

        set_time_limit(0);
        session_write_close();
        ignore_user_abort(true);

        $app['task-manager']->getSchedulerProcess()->run();

        return $app->json(true);
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }


}
