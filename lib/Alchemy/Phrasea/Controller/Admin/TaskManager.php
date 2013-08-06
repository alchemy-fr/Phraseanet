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

use Alchemy\Phrasea\Exception\XMLParseErrorException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

class TaskManager implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireRight('taskmanager');
        });

        $controllers->get('/', function(Application $app, Request $request) {
            return $app->redirectPath('admin_tasks_list');
        })->bind('admin_tasks');

        $controllers->get('/tasks/', function(Application $app, Request $request) {

            if ($request->getContentType() == 'json') {
                return $app->json($app['task-manager']->toArray());
            } else {

                $template = 'admin/tasks/list.html.twig';

                return $app['twig']->render($template, array(
                        'task_manager'  => $app['task-manager'],
                        'scheduler_key' => \phrasea::scheduler_key($app)
                    ));
            }
        })->bind('admin_tasks_list');

        $controllers->post('/tasks/create/', function(Application $app, Request $request) {
            if (!class_exists($className = $request->request->get('tcl'))) {
                $app->abort(400, sprintf('Unknown task %s', $className));
            }

            $task = $className::create($app);
            $tid = $task->getId();

            return $app->redirectPath('admin_tasks_task_show', array('id' => $tid));
        })->bind('admin_tasks_task_create');

        /*
         * route /admin/taskmanager/scheduler/start
         */
        $controllers->get('/scheduler/start', $this->call('startScheduler'))
            ->bind('admin_tasks_scheduler_start');

        /*
         * route /admin/scheduler/stop
         */
        $controllers->get('/scheduler/stop', function(Application $app, Request $request) use ($app) {
            try {
                $app['task-manager']->setSchedulerState(\task_manager::STATE_TOSTOP);

                return $app->json(array('success' => true));
            } catch (Exception $e) {

            }

            return $app->json(array('success' => false));
        })->bind('admin_tasks_scheduler_stop');

        $controllers->get('/scheduler/log', function(Application $app, Request $request) {
            $logdir = $app['root.path'] . '/logs/';

            $rname = '/scheduler((\.log)|(-.*\.log))$/';

            $finder = new Finder();
            $finder
                ->files()->name($rname)
                ->in($logdir)
                ->sortByModifiedTime();

            $found = false;
            foreach ($finder->getIterator() as $file) {
                if ($request->query->get('clr') == $file->getFilename()) {
                    file_put_contents($file->getRealPath(), '');
                    $found = true;
                }
            }
            if ($found) {
                return $app->redirectPath('admin_tasks_scheduler_log');
            }

            return $app->stream(function() use ($finder, $app) {
                foreach ($finder->getIterator() as $file) {
                    printf("<h4>%s\n", $file->getRealPath());
                    printf("&nbsp;<a href=\"".$app->path('admin_tasks_scheduler_log', array('clr' => $file->getFilename()))."\">%s</a>"
                        , _('Clear')
                    );
                    print("</h4>\n<pre>\n");
                    print(htmlentities(file_get_contents($file->getRealPath())));
                    print("</pre>\n");

                    ob_flush();
                    flush();
                }
            }, 200, array('Content-Type' => 'text/html'));
        })->bind('admin_tasks_scheduler_log');

        $controllers->get('/task/{id}/log', function(Application $app, Request $request, $id) {
            $logdir = $app['root.path'] . '/logs/';

            $rname = '/task_' . $id . '((\.log)|(-.*\.log))$/';

            $finder = new Finder();
            $finder
                ->files()->name($rname)
                ->in($logdir)
                ->sortByModifiedTime();

            $found = false;
            foreach ($finder->getIterator() as $file) {
                if ($request->query->get('clr') == $file->getFilename()) {
                    file_put_contents($file->getRealPath(), '');
                    $found = true;
                }
            }
            if ($found) {
                return $app->redirectPath('admin_tasks_task_log', array('id' => $id));
            }

            return $app->stream(function() use ($finder, $id) {
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
        })->bind('admin_tasks_task_log');

        $controllers->get('/task/{id}/delete', function(Application $app, Request $request, $id) {

            try {
                $task = $app['task-manager']->getTask($id);
                $task->delete();

                return $app->redirectPath('admin_tasks_list');
            } catch (\Exception $e) {

                /**
                 * todo : add a message back
                 */

                return $app->redirectPath('admin_tasks_list');
            }
        })->bind('admin_tasks_task_delete');

        $controllers->get('/task/{id}/tostart', function(Application $app, Request $request, $id) {

            $ret = false;
            try {
                $task = $app['task-manager']->getTask($id);
                $pid = (int) ($task->getPID());
                if (!$pid) {
                    $task->setState(\task_abstract::STATE_TOSTART);
                    $ret = true;
                }
            } catch (Exception $e) {

            }

            return $app->json($ret);
        })->bind('admin_tasks_task_start');

        $controllers->get('/task/{id}/tostop', function(Application $app, Request $request, $id) {

            $ret = false;
            try {
                $task = $app['task-manager']->getTask($id);
                $pid = $task->getPID();
                $signal = $request->query->get('signal');
                $task->setState(\task_abstract::STATE_TOSTOP);

                if ((int) $pid > 0 && (int) $signal > 0 && function_exists('posix_kill')) {
                    posix_kill((int) $pid, (int) $signal);
                }

                $ret = true;
            } catch (Exception $e) {

            }

            return $app->json($ret);
        })->bind('admin_tasks_task_stop');

        $controllers->get('/task/{id}/resetcrashcounter/', function(Application $app, Request $request, $id) {

            try {
                $task = $app['task-manager']->getTask($id);

                $task->resetCrashCounter();

                return $app->json(array('success' => true));
            } catch (\Exception $e) {
                return $app->json(array('success' => false));
            }
        })->bind('admin_tasks_task_reset');

        /*
         * route /admin/task-manager/task/{id}/save
         * return json
         */
        $controllers->post('/task/{id}/save/', function(Application $app, Request $request, $id) {

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->strictErrorChecking = true;
            try {
                if (!@$dom->loadXML($request->request->get('xml'))) {
                    throw new XMLParseErrorException($request->request->get('xml'));
                }
            } catch (XMLParseErrorException $e) {
                return $app->json(array('success' => false, 'message' => $e->getMessage()));
            }

            try {
                $task = $app['task-manager']->getTask($id);

                $task->setTitle($request->request->get('title'));
                $task->setActive(\p4field::isyes($request->request->get('active')));
                $task->setSettings($request->request->get('xml'));

                return $app->json(array('success' => true));
            } catch (\Exception $e) {
                return $app->json(array('success' => false, 'message' => 'Task not found'));
            }
        })->bind('admin_tasks_task_save');

        /*
         * route /admin/task-manager/task/{id}/facility/
         * call callback(s) of a task, for ex. to transform gui(form) to xml settings
         */
        $controllers->post('/task/{id}/facility/', function(Application $app, Request $request, $id) {

            $ret = '';
            try {
                $task = $app['task-manager']->getTask($id);
            } catch (\Exception $e) {
                return new Response(
                        'Bad task ID',
                        404    // Not Found
                );
            }

            switch ($request->request->get('__action')) {
                case 'FORM2XML':
                    if (@simplexml_load_string($request->request->get('__xml'))) {
                        $ret = $task->graphic2xml($request->request->get('__xml'));
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
        })->bind('admin_tasks_task_facility');

        $controllers->get('/task/{id}', function(Application $app, Request $request, $id) {

            $task = $app['task-manager']->getTask($id);

            $template = 'admin/task.html.twig';

            return $app['twig']->render($template, array(
                    'task' => $task,
                    'view' => 'XML'
                ));
        })->bind('admin_tasks_task_show');

        /*
         * route /admin/task/checkxml/
         * check if the xml is valid
         */
        $controllers->post('/task/checkxml/', function(Application $app, Request $request) {
            $ret = array('ok'                      => true, 'err'                     => null);
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->strictErrorChecking = true;
            try {
                if (!@$dom->loadXML($request->request->get('xml'))) {
                    throw new XMLParseErrorException($request->request->get('xml'));
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
        $app['session']->save();
        set_time_limit(0);
        ignore_user_abort(true);

        $app['task-manager']->getSchedulerProcess()->run();

        return $app->json(array('success' => true));
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
