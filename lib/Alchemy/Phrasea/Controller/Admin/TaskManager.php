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

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Form\TaskForm;
use Entities\Task;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskManager implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.admin.task'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireRight('taskmanager');
        });

        $converter = function ($id) use ($app) {
            return $app['converter.task']->convert($id);
        };

        $controllers
            ->get('/', 'controller.admin.task:getRoot')
            ->bind('admin_tasks');

        $controllers
            ->get('/tasks', 'controller.admin.task:getTasks')
            ->bind('admin_tasks_list');

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

    public function startScheduler(Application $app, Request $request)
    {
        $app['task-manager.status']->start();

        return $app->redirectPath('admin_tasks_list');
    }

    public function stopScheduler(Application $app, Request $request)
    {
        $app['task-manager.status']->stop();

        return $app->redirectPath('admin_tasks_list');
    }

    public function getRoot(Application $app, Request $request)
    {
        return $app->redirectPath('admin_tasks_list');
    }

    public function getTasks(Application $app, Request $request)
    {
        return $app['twig']->render('admin/task-manager/list.html.twig', array(
            'available_jobs'  => $app['task-manager.available-jobs'],
            'tasks'           => $app['manipulator.task']->getRepository()->findAll(),
        ));
    }

    public function postCreateTask(Application $app, Request $request)
    {
        try {
            $job = $app['task-manager.job-factory']->create($request->request->get('job-name'));
        } catch (InvalidArgumentException $e) {
            $app->abort(400, $e->getMessage());
        }

        $task = $app['manipulator.task']->create(
            $job->getName(),
            $job->getJobId(),
            $job->getEditor()->getDefaultSettings($app['phraseanet.configuration']),
            $job->getEditor()->getDefaultPeriod()
        );

        return $app->redirectPath('admin_tasks_task_show', array('task' => $task->getId()));
    }

    public function getSchedulerLog(Application $app, Request $request)
    {
        $logFile = $app['task-manager.log-file.factory']->forManager();
        if ($request->query->get('clr')) {
            $logFile->clear();
        }

        return $app['twig']->render('admin/task-manager/log.html.twig', array(
            'logfile' => $logFile,
            'logname' => 'Scheduler',
        ));
    }

    public function getTaskLog(Application $app, Request $request, Task $task)
    {
        $logFile = $app['task-manager.log-file.factory']->forTask($task);
        if ($request->query->get('clr')) {
            $logFile->clear();
        }

        return $app['twig']->render('admin/task-manager/log.html.twig', array(
            'logfile' => $logFile,
            'logname' => sprintf('%s (task id %d)', $task->getName(), $task->getId()),
        ));
    }

    public function postTaskDelete(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->delete($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postStartTask(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->start($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postStopTask(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->stop($task);

        return $app->redirectPath('admin_tasks_list');
    }

    public function postResetCrashes(Application $app, Request $request, Task $task)
    {
        $app['manipulator.task']->resetCrashes($task);

        return $app->json(array('success' => true));
    }

    public function postSaveTask(Application $app, Request $request, Task $task)
    {
        if (!$this->doValidateXML($request->request->get('settings'))) {
            return $app->json(array('success' => false, 'message' => sprintf('Unable to load XML %s', $request->request->get('xml'))));
        }

        $form = $app->form(new TaskForm());
        $form->setData($task);
        $form->bind($request);
        if ($form->isValid())
        {
            $app['manipulator.task']->update($task);

            return $app->json(array('success' => true));
        }

        return $app->json(array(
            'success' => false,
            'message' => implode("\n", $form->getErrors())
        ));
    }

    public function postTaskFacility(Application $app, Request $request, Task $task)
    {
        return $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor()
            ->facility($app, $request);
    }

    public function postXMLFromForm(Application $app, Request $request, Task $task)
    {
        return $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor()
            ->updateXMLWithRequest($request);
    }

    public function getTask(Application $app, Request $request, Task $task)
    {
        $editor = $app['task-manager.job-factory']
            ->create($task->getJobId())
            ->getEditor();

        $form = $app->form(new TaskForm());
        $form->setData($task);

        return $app['twig']->render($editor->getTemplatePath(), array(
            'task' => $task,
            'form' => $form->createView(),
            'view' => 'XML',
        ));
    }

    public function validateXML(Application $app, Request $request)
    {
        $ret = array(
            'success' => true,
        );

        if (!$this->doValidateXML($request->getContent())) {
            $ret = array(
                'success' => false,
            );
        }

        return $app->json($ret);
    }

    private function doValidateXML($string)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->strictErrorChecking = true;

        return (Boolean) @$dom->loadXML($string);
    }
}
