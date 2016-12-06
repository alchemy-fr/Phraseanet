<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Form\TaskForm;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\Model\Repositories\TaskRepository;
use Alchemy\Phrasea\TaskManager\Job\Factory;
use Alchemy\Phrasea\TaskManager\LiveInformation;
use Alchemy\Phrasea\TaskManager\Log\LogFileFactory;
use Alchemy\Phrasea\TaskManager\TaskManagerStatus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;

class TaskManagerController extends Controller
{
    use DispatcherAware;

    public function startScheduler()
    {
        /** @var TaskManagerStatus $status */
        $status = $this->app['task-manager.status'];
        $status->start();
        
        $cmdLine = sprintf(
            '%s %s %s',
            $this->getConf()->get(['main', 'binaries', 'php_binary']),
            realpath(__DIR__ . '/../../../../../bin/console'),
            'task-manager:scheduler:run'
        );

        $this->getDispatcher()->addListener(KernelEvents::TERMINATE, function () use ($cmdLine) {
            $process = new Process($cmdLine);

            $process->setTimeout(0);
            $process->disableOutput();

            set_time_limit(0);
            ignore_user_abort(true);

            $process->run();
        }, -1000);

        return $this->app->redirectPath('admin_tasks_list');
    }

    public function stopScheduler()
    {
        /** @var TaskManagerStatus $status */
        $status = $this->app['task-manager.status'];
        $status->stop();

        $info = $this->getLiveInformationRequest();
        $data = $info->getManager();

        if (null !== $pid = $data['process-id']) {
            if (substr(php_uname(), 0, 7) == "Windows"){
                exec(sprintf('TaskKill /PID %d', $pid));
            } else {
                exec(sprintf('kill %d', $pid));
            }
        }

        return $this->app->redirectPath('admin_tasks_list');
    }

    public function getRoot()
    {
        return $this->app->redirectPath('admin_tasks_list');
    }

    public function getLiveInformation(Request $request)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ($request->getRequestFormat() !== "json") {
            $this->app->abort(406, 'Only JSON format is accepted.');
        }

        $tasks = [];
        /** @var Task $task */
        foreach ($this->getTaskRepository()->findAll() as $task) {
            $tasks[$task->getId()] = $this->getLiveInformationRequest()->getTask($task);
        }

        return $this->app->json([
            'manager' => $this->getLiveInformationRequest()->getManager(),
            'tasks' => $tasks
        ]);
    }

    public function getScheduler(Request $request)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ($request->getRequestFormat() !== "json") {
            $this->app->abort(406, 'Only JSON format is accepted.');
        }

        return $this->app->json([
            'name' => $this->app->trans('Task Scheduler'),
            'configuration' => $this->app['task-manager.status']->getStatus(),
            'urls' => [
                'start' => $this->app->path('admin_tasks_scheduler_start'),
                'stop' => $this->app->path('admin_tasks_scheduler_stop'),
                'log' => $this->app->path('admin_tasks_scheduler_log'),
            ]
        ]);
    }

    public function getTasks(Request $request)
    {
        $tasks = [];

        /** @var Task $task */
        foreach ($this->getTaskRepository()->findAll() as $task) {
            $tasks[] = [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'status' => $task->getStatus()
            ];
        }

        if ($request->getRequestFormat() === "json") {
            foreach ($tasks as $k => $task) {
                $tasks[$k]['urls'] = $this->getTaskResourceUrls($task['id']);
            }

            return $this->app->json($tasks);
        }

        return $this->app['twig']->render('admin/task-manager/index.html.twig', [
            'available_jobs' => $this->app['task-manager.available-jobs'],
            'tasks' => $tasks,
            'scheduler' => [
                'id'   => null,
                'name' => $this->app->trans('Task Scheduler'),
                'status' => $this->app['task-manager.status']->getStatus(),
            ]
        ]);
    }

    public function postCreateTask(Request $request)
    {
        try {
            /** @var Factory $factory */
            $factory = $this->app['task-manager.job-factory'];
            $job = $factory->create($request->request->get('job-name'));
        } catch (InvalidArgumentException $e) {
            throw new HttpException(400, $e->getMessage(), $e);
        }

        $task = $this->getTaskManipulator()->create(
            $job->getName(),
            $job->getJobId(),
            $job->getEditor()->getDefaultSettings($this->app['conf']),
            $job->getEditor()->getDefaultPeriod()
        );

        return $this->app->redirectPath('admin_tasks_task_show', ['task' => $task->getId()]);
    }

    public function getSchedulerLog(Request $request)
    {
        /** @var LogFileFactory $factory */
        $factory = $this->app['task-manager.log-file.factory'];
        $logFile = $factory->forManager();
        if ($request->query->get('clr') && $request->query->get('version') !== null) {
            $logFile->clear($request->query->get('version'));
        }

        return $this->render('admin/task-manager/log_scheduler.html.twig', [
            'logfile' => $logFile,
            'version' => $request->query->get('version')
        ]);
    }

    public function getTaskLog(Request $request, Task $task)
    {
        /** @var LogFileFactory $factory */
        $factory = $this->app['task-manager.log-file.factory'];
        $logFile = $factory->forTask($task);

        if ($request->query->get('clr') && $request->query->get('version') !== null) {
            $logFile->clear($request->query->get('version'));
        }

        return $this->render('admin/task-manager/log_task.html.twig', [
            'logfile' => $logFile,
            'version' => $request->query->get('version')
        ]);
    }

    public function postTaskDelete(Task $task)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $this->getTaskManipulator()->delete($task);

        return $this->app->redirectPath('admin_tasks_list');
    }

    public function postStartTask(Task $task)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $this->getTaskManipulator()->start($task);

        return $this->app->redirectPath('admin_tasks_list');
    }

    public function postStopTask(Task $task)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        $this->getTaskManipulator()->stop($task);

        return $this->app->redirectPath('admin_tasks_list');
    }

    public function postResetCrashes(Task $task)
    {
        $this->getTaskManipulator()->resetCrashes($task);

        return $this->app->json(['success' => true]);
    }

    public function postSaveTask(Request $request, Task $task)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if (!$this->doValidateXML($request->request->get('settings'))) {
            return $this->app->json(['success' => false, 'message' => sprintf('Unable to load XML %s', $request->request->get('xml'))]);
        }

        $form = $this->app->form(new TaskForm());
        $form->setData($task);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->getTaskManipulator()->update($task);

            return $this->app->json(['success' => true]);
        }
        return $this->app->json([
            'success' => false,
            'message' => implode("\n", iterator_to_array($form->getErrors())),
        ]);
    }

    public function postTaskFacility(Request $request, Task $task)
    {
        return $this->getJobFactory()
            ->create($task->getJobId())
            ->getEditor()
            ->facility($this->app, $request);
    }

    public function postXMLFromForm(Request $request, Task $task)
    {
        return $this->getJobFactory()
            ->create($task->getJobId())
            ->getEditor()
            ->updateXMLWithRequest($request);
    }

    public function getTask(Request $request, Task $task)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        if ('json' === $request->getContentType()) {
            return $this->app->json(array_replace([
                'id' => $task->getId(),
                'name' => $task->getName(),
                'urls' => $this->getTaskResourceUrls($task->getId())
            ],
                $this->getLiveInformationRequest()->getTask($task)
            ));
        }

        $editor = $this->getJobFactory()
            ->create($task->getJobId())
            ->getEditor();

        $form = $this->app->form(new TaskForm());
        $form->setData($task);

        return $this->render($editor->getTemplatePath(), [
            'task' => $task,
            'form' => $form->createView(),
            'view' => 'XML',
        ]);
    }

    public function validateXML(Request $request)
    {
        if (false === $this->app['phraseanet.configuration']['main']['task-manager']['enabled']) {
            throw new RuntimeException('The use of the task manager is disabled on this instance.');
        }

        return $this->app->json(['success' => $this->doValidateXML($request->getContent())]);
    }

    private function doValidateXML($string)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->strictErrorChecking = true;

        return (Boolean) @$dom->loadXML($string);
    }

    private function getTaskResourceUrls($taskId)
    {
        return [
            'show' => $this->app->path('admin_tasks_task_show', ['task' => $taskId]),
            'start' => $this->app->path('admin_tasks_task_start', ['task' => $taskId]),
            'stop' => $this->app->path('admin_tasks_task_stop', ['task' => $taskId]),
            'delete' => $this->app->path('admin_tasks_task_delete', ['task' => $taskId]),
            'log' => $this->app->path('admin_tasks_task_log', ['task' => $taskId]),
        ];
    }

    /**
     * @return TaskRepository
     */
    private function getTaskRepository()
    {
        return $this->app['repo.tasks'];
    }

    /**
     * @return LiveInformation
     */
    private function getLiveInformationRequest()
    {
        return $this->app['task-manager.live-information'];
    }

    /**
     * @return TaskManipulator
     */
    private function getTaskManipulator()
    {
        return $this->app['manipulator.task'];
    }

    /**
     * @return Factory
     */
    private function getJobFactory()
    {
        return $this->app['task-manager.job-factory'];
    }
}
