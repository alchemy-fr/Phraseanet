<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Admin;

use Alchemy\Phrasea\Controller\Admin\TaskManagerController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Model\Converter\TaskConverter;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class TaskManager implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.task'] = $app->share(function (\Alchemy\Phrasea\Application $app) {
            return (new TaskManagerController($app))
                ->setDispatcher($app['dispatcher']);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $converter = function ($task) use ($app) {
            /** @var TaskConverter $converter */
            $converter = $app['converter.task'];
            return $converter->convert($task);
        };

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight(\ACL::TASKMANAGER);
        });

        $controllers
            ->get('/', 'controller.admin.task:getRoot')
            ->bind('admin_tasks');

        $controllers
            ->get('/tasks', 'controller.admin.task:getTasks')
            ->bind('admin_tasks_list');

        $controllers
            ->get('/scheduler', 'controller.admin.task:getScheduler')
            ->bind('admin_scheduler');

        $controllers
            ->get('/live', 'controller.admin.task:getLiveInformation')
            ->bind('admin_tasks_live_info');

        $controllers
            ->post('/tasks/create', 'controller.admin.task:postCreateTask')
            ->bind('admin_tasks_task_create');

        $controllers
            ->post('/scheduler/start','controller.admin.task:startScheduler')
            ->bind('admin_tasks_scheduler_start');

        $controllers
            ->post('/scheduler/stop','controller.admin.task:stopScheduler')
            ->bind('admin_tasks_scheduler_stop');

        $controllers
            ->get('/scheduler/log', 'controller.admin.task:getSchedulerLog')
            ->bind('admin_tasks_scheduler_log');

        $controllers
            ->get('/task/{task}/log', 'controller.admin.task:getTaskLog')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_log');

        $controllers
            ->post('/task/{task}/delete', 'controller.admin.task:postTaskDelete')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_delete');

        $controllers
            ->post('/task/{task}/start', 'controller.admin.task:postStartTask')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_start');

        $controllers
            ->post('/task/{task}/stop', 'controller.admin.task:postStopTask')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_stop');

        $controllers
            ->post('/task/{task}/resetcrashcounter', 'controller.admin.task:postResetCrashes')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_reset');

        $controllers
            ->post('/task/{task}/save', 'controller.admin.task:postSaveTask')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_save');

        $controllers
            ->post('/task/{task}/facility', 'controller.admin.task:postTaskFacility')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_facility');

        $controllers
            ->post('/task/{task}/xml-from-form', 'controller.admin.task:postXMLFromForm')
            ->convert('task', $converter)
            ->bind('admin_tasks_xml_from_form');

        $controllers
            ->get('/task/{task}', 'controller.admin.task:getTask')
            ->convert('task', $converter)
            ->bind('admin_tasks_task_show');

        $controllers
            ->post('/task/validate-xml', 'controller.admin.task:validateXML')
            ->bind('admin_tasks_validate_xml');

        return $controllers;
    }
}
