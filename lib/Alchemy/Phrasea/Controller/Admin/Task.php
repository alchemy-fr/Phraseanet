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

class XMLParseErrorException extends \Exception
{
    private $_XMLErrMessage = '';

    public function getXMLErrMessage()
    {
        return $this->_XMLErrMessage;
    }

    public function __construct($xml)
    {
        set_error_handler(array($this, "errorHandler"));
        $dom = new \DomDocument('1.0', 'UTF-8');
        @$dom->loadXML($xml);
        restore_error_handler();
        $this->message = "XML Parse Error";
        parent::__construct();
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
//        var_dump($errno, $errstr, $errfile, $errline);
//        $pos = strpos($errstr,"]:") ;
//        if ($pos) {
//            $errstr = substr($errstr,$pos+ 2);
//        }
        $this->_XMLErrMessage = $errstr;
    }
}

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Task implements ControllerProviderInterface
{

    public function connect(Application $app)
    {

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/task/{id}/log
         *  show logs of a task
         */
        $controllers->get('/{id}/log', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
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
        $controllers->get('/{id}/delete', function(Application $app, Request $request, $id) {
//                $appbox = \appbox::get_instance($app['phraseanet.core']);
//                $task_manager = new \task_manager($appbox);

                try {
                    $task = $app['task-manager']->getTask($id);
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
        $controllers->get('/{id}/tostart', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
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
        $controllers->get('/{id}/tostop', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
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
        $controllers->get('/{id}/resetcrashcounter/', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
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
         * route /admin/task/{id}/save
         * return json
         */
        $controllers->post('/{id}/save/', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
                $task_manager = new \task_manager($appbox);

                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->strictErrorChecking = true;
                try {
                    if ( ! @$dom->loadXML($request->get('xml'))) {
                        throw new XMLParseErrorException($request->get('xml'));
                    }


                } catch (XMLParseErrorException $e) {
                    return  new Response(
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
         * route /admin/task/{id}/facility/
         * call callback(s) of a task, for ex. to transform gui(form) to xml settings
         */
        $controllers->post('/{id}/facility/', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
                $task_manager = new \task_manager($appbox);
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
                        if(method_exists($task, 'facility'))
                        {
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
         * route /admin/task/{id}
         *  render a task editing interface
         */
        $controllers->get('/{id}', function(Application $app, Request $request, $id) {
                $appbox = \appbox::get_instance($app['phraseanet.core']);
                $task_manager = new \task_manager($appbox);
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
        $controllers->post('/checkxml/', function(Application $app, Request $request) {
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
}
