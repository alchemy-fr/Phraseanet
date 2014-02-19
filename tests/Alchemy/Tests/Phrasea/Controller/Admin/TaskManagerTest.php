<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\HttpFoundation\Response;

class TaskManagerTest extends \PhraseanetAuthenticatedWebTestCase
{
    public function testRouteTaskManagerRoot()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/');
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());
    }

    public function testRootListTasks()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/tasks');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testRootListTasksJson()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/tasks', [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT" => "application/json"]);
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $tasks = json_decode(self::$DI['client']->getResponse()->getContent());
        foreach ($tasks as $task) {
            $this->assertObjectHasAttribute('id', $task);
            $this->assertObjectHasAttribute('name', $task);
            $this->assertObjectHasAttribute('configuration', $task);
            $this->assertObjectHasAttribute('actual', $task);
            $this->assertObjectHasAttribute('urls', $task);
        }
    }

    public function testRootPostCreateTask()
    {
        $parameters = [
            'job-name' => 'Alchemy\Phrasea\TaskManager\Job\NullJob',
            '_token' => 'token',
        ];

        self::$DI['client']->request('POST', '/admin/task-manager/tasks/create', $parameters);
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertRegExp('/\/admin\/task-manager\/task\/\d+/', self::$DI['client']->getResponse()->headers->get('location'));
    }

    public function testPostCreateTaskWithWrongName()
    {
        $parameters = [
            'job-name' => 'NoJob',
            '_token' => 'token',
        ];

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
        self::$DI['client']->request('GET', '/admin/task-manager/task/1/log');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testPostTaskDelete()
    {
        self::$DI['client']->request('POST', '/admin/task-manager/task/1/delete');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertNull(self::$DI['app']['EM']->find('Phraseanet:Task', 1));
    }

    public function testPostTaskStart()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/start');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertEquals(Task::STATUS_STARTED, $task->getStatus());
    }

    public function testPostTaskStop()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/stop');
        $this->assertEquals(302, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('/admin/task-manager/tasks', self::$DI['client']->getResponse()->headers->get('location'));

        $this->assertEquals(Task::STATUS_STOPPED, $task->getStatus());
    }

    public function testPostResetCrashes()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        self::$DI['client']->request('POST', '/admin/task-manager/task/'.$task->getId().'/resetcrashcounter');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));

        $this->assertEquals(0, $task->getCrashed());
    }

    public function testPostSaveTask()
    {
        $task = self::$DI['app']['EM']->find('Phraseanet:Task', 1);

        $name = 'renamed';
        $period = 366;
        $status = Task::STATUS_STOPPED;
        $settings = '<?xml version="1.0" encoding="UTF-8"?><tasksettings><neutron></neutron></tasksettings>';

        $parameters = [
            '_token'   => 'token',
            'name'     => $name,
            'period'   => $period,
            'status'   => $status,
            'settings' => $settings,
        ];

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
        self::$DI['client']->request('GET', '/admin/task-manager/task/1');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testGetTaskJson()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/task/1', [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT" => "application/json"]);
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $json = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertObjectHasAttribute('id', $json);
        $this->assertObjectHasAttribute('name', $json);
        $this->assertObjectHasAttribute('configuration', $json);
        $this->assertObjectHasAttribute('actual', $json);
        $this->assertObjectHasAttribute('urls', $json);
    }

    public function testGetSchedulerJson()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/scheduler', [], [], ["HTTP_CONTENT_TYPE" => "application/json", "HTTP_ACCEPT" => "application/json"]);
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $json = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertObjectHasAttribute('name', $json);
        $this->assertObjectHasAttribute('configuration', $json);
        $this->assertObjectHasAttribute('actual', $json);
        $this->assertObjectHasAttribute('urls', $json);
    }

    public function testGetSchedulerJsonBadRequest()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/scheduler');
        $this->assertEquals(406, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testGetInvalidTask()
    {
        self::$DI['client']->request('GET', '/admin/task-manager/task/50');
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testValidateInvalidXML()
    {
        self::$DI['client']->request('POST', '/admin/task-manager/task/validate-xml', [], [], [], 'Invalid XML');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));
        $this->assertEquals(['success' => false], json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testValidateXML()
    {
        self::$DI['client']->request('POST', '/admin/task-manager/task/validate-xml', [], [], [],
           '<?xml version="1.0" encoding="UTF-8"?><tasksettings><neutron></neutron></tasksettings>');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
        $this->assertEquals('application/json', self::$DI['client']->getResponse()->headers->get('content-type'));
        $this->assertEquals(['success' => true], json_decode(self::$DI['client']->getResponse()->getContent(), true));
    }

    public function testPostTaskFacility()
    {
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

        self::$DI['client']->request('POST', '/admin/task-manager/task/1/facility');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testPostTaskXmlFromForm()
    {
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

        self::$DI['client']->request('POST', '/admin/task-manager/task/1/xml-from-form');
        $this->assertEquals(200, self::$DI['client']->getResponse()->getStatusCode());
    }
}
