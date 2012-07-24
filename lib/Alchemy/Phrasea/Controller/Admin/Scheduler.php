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
        $appbox = \appbox::get_instance($app['phraseanet.core']);

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/scheduler/start
         */
        $controllers->get('/start', function(Application $app, Request $request) use ($app, $appbox) {

                $session = $appbox->get_session();
                $scheduler_key = \phrasea::scheduler_key();
                $registry = $appbox->get_registry();

                $good_user = false;
                if ($session->is_authenticated()) {
                    $user = \User_Adapter::getInstance($session->get_usr_id(), $appbox);
                    if ($user->ACL()->has_right('taskmanager'))
                        $good_user = true;
                }

                if ( ! $good_user && (trim($scheduler_key) == '' || $scheduler_key !== $request->get('key'))) {
                    phrasea::headers(403);
                }

                set_time_limit(0);
                session_write_close();
                ignore_user_abort(true);

                $nullfile = '/dev/null';

                if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                    $nullfile = 'NUL';
                }

                $phpcli = $registry->get('GV_cli');

                $cmd = $phpcli . ' -f ' . $registry->get('GV_RootPath') . "bin/console scheduler:start";

                $descriptors[1] = array("file", $nullfile, "a+");
                $descriptors[2] = array("file", $nullfile, "a+");

                $pipes = null;
                $cwd = $registry->get('GV_RootPath') . "bin/";
                $proc = proc_open($cmd, $descriptors, $pipes, $cwd, null, array('bypass_shell' => true));

                $pid = NULL;
                if (is_resource($proc)) {
                    $proc_status = proc_get_status($proc);
                    if ($proc_status['running'])
                        $pid = $proc_status['pid'];
                }
                if ($pid !== NULL) {
                    $msg = sprintf("scheduler '%s' started (pid=%s)", $cmd, $pid);
                    // my_syslog(LOG_INFO, $msg);
                } else {
                    @fclose($pipes[1]);
                    @fclose($pipes[2]);
                    @proc_close($process);

                    $msg = sprintf("scheduler '%s' failed to start", $cmd);
                    // my_syslog(LOG_INFO, $msg);
                }

                return $app->json(true);
            });

        /*
         * route /admin/scheduler/stop
         */
        $controllers->get('/stop', function(Application $app, Request $request) use ($app, $appbox) {
                 try {
                    $task_manager = new \task_manager($appbox);

                    $task_manager->setSchedulerState(\task_manager::STATE_TOSTOP);

                    return $app->json(true);
                } catch (Exception $e) {

                }

                return $app->json(false);
            });

        return $controllers;
    }
}
