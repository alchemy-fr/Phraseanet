<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Entities\Task;
use Symfony\Component\HttpFoundation\Response;

class TaskManagerTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testRouteTaskManagerRoot()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testRootListTasks()
    {
        foreach (self::$DI['app']['task-manager.available-jobs'] as $job) {
            $task = new Task();
            $task
                ->setName('task')
                ->setJobId(get_class($job));
            self::$DI['app']['EM']->persist($task);
        }
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('GET', '/admin/task-manager/tasks');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testRootPostCreateTask()
    {
        $parameters = array(
            'job-name' => 'Alchemy\Phrasea\TaskManager\Job\NullJob',
            '_token' => 'token',
        );

        self::$DI['client']->request('POST', '/admin/task-manager/tasks/create', $parameters);
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertRegExp('/\/admin\/task-manager\/task\/\d+/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostCreateTaskWithWrongName()
    {
        $parameters = array(
            'job-name' => 'NoJob',
            '_token' => 'token',
        );

        self::$DI['client']->request('POST', '/admin/task-manager/tasks/create', $parameters);
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testPostStartScheduler()
    {
        self::$DI['app']['task-manager.status'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
                ->disableOriginalConstructor()
                ->getMock();
        self::$DI['app']['task-manager.status']->expects($this->once())
               ->method('start');
        self::$DI['client']->request('POST', '/admin/task-manager/scheduler/start');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostStopScheduler()
    {
        self::$DI['app']['task-manager.status'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\TaskManagerStatus')
                ->disableOriginalConstructor()
                ->getMock();
        self::$DI['app']['task-manager.status']->expects($this->once())
               ->method('stop');
        self::$DI['client']->request('POST', '/admin/task-manager/scheduler/stop');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testGetSchedulerLog()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/scheduler/log');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testGetTaskLog()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('GET', '/admin/task-manager/task/'.$task->getId().'/log');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testPostTaskDelete()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();
        $taskId = $task->getId();

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$taskId.'/delete');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertNull(self::$DI['app']['EM']->find('Entities\Task', $taskId));
    }

    public function testPostTaskStart()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setStatus(Task::STATUS_STOPPED)
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/start');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testPostTaskStop()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setStatus(Task::STATUS_STARTED)
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/stop');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testPostResetCrashes()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setCrashed(30)
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/resetcrashcounter');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));

        $this->assertEquals(0, $task->getCrashed());
    }

    public function testPostSaveTask()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $name = 'renamed';
        $period = 366;
        $status = Task::STATUS_STOPPED;
        $settings = '<?xml version="1.0" encoding="UTF-8"?><tasksettings><neutron></neutron></tasksettings>';

        $parameters = array(
            '_token'   => 'token',
            'name'     => $name,
            'period'   => $period,
            'status'   => $status,
            'settings' => $settings,
        );

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/save', $parameters);

        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));

        $this->assertEquals($name, $task->getName());
        $this->assertEquals($period, $task->getPeriod());
        $this->assertEquals($status, $task->getStatus());
        $this->assertEquals($settings, $task->getSettings());
    }

    public function testGetTask()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('GET', '/admin/task-manager/task/'.$task->getId());
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testGetInvalidTask()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/task/50');
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testValidateInvalidXML()
    {
        self::$DI['client']->request('POST', '/admin/task-manager/task/validate-xml', array(), array(), array(), 'Invalid XML');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));
        $this->assertEquals(array('success' => false), json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testValidateXML()
    {
        self::$DI['client']->request('POST', '/admin/task-manager/task/validate-xml', array(), array(), array(),
           '<?xml version="1.0" encoding="UTF-8"?><tasksettings><neutron></neutron></tasksettings>');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));
        $this->assertEquals(array('success' => true), json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testPostTaskFacility()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $job = $this->getMock('Alchemy\Phrasea\TaskManager\Job\JobInterface');
        $editor = $this->getMock('Alchemy\Phrasea\TaskManager\Editor\EditorInterface');

        $job->expects($this->once())
                ->method('getEditor')
                ->will($this->returnValue($editor));
        $editor->expects($this->once())
                ->method('facility')
                ->will($this->returnValue(new Response('Hello')));

        self::$DI['app']['task-manager.job-factory'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Job\Factory')
                ->disableOriginalConstructor()->getMock();
        self::$DI['app']['task-manager.job-factory']->expects($this->once())
                ->method('create')
                ->will($this->returnValue($job));

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/facility');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testPostTaskXmlFromForm()
    {
        $task = new Task();
        $task
            ->setName('task')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');
        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $job = $this->getMock('Alchemy\Phrasea\TaskManager\Job\JobInterface');
        $editor = $this->getMock('Alchemy\Phrasea\TaskManager\Editor\EditorInterface');

        $job->expects($this->once())
                ->method('getEditor')
                ->will($this->returnValue($editor));
        $editor->expects($this->once())
                ->method('updateXMLWithRequest')
                ->will($this->returnValue(new Response('Hello')));

        self::$DI['app']['task-manager.job-factory'] = $this->getMockBuilder('Alchemy\Phrasea\TaskManager\Job\Factory')
                ->disableOriginalConstructor()->getMock();
        self::$DI['app']['task-manager.job-factory']->expects($this->once())
                ->method('create')
                ->will($this->returnValue($job));

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/xml-from-form');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }
}
