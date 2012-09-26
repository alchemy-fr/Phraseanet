<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class TaskManagerTest extends \PhraseanetWebTestCaseAuthenticatedAbstract {

    public function testRouteTaskManager() {
        /**
         * get /admin/task-manager/ should redirect to /admin/task-manager/tasks
         */
        self::$DI['client']->request(
                'GET', '/admin/task-manager/', array()
        );
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect('/admin/task-manager/tasks/'));
    }

    public function testRouteTaskManager_tasks() {
        $task_manager = new \task_manager(self::$DI['app']);

        $crawler = self::$DI['client']->request(
                'GET', '/admin/task-manager/tasks/', array()
        );
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('form#taskManagerForm'));

        $crawler = self::$DI['client']->request(
                'GET', '/admin/task-manager/tasks/', array(), array(), array('CONTENT_TYPE' => 'application/json')
        );
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertTrue(self::$DI['client']->getResponse()->headers->contains('Content-Type', 'application/json'));

        $raw = self::$DI['client']->getResponse()->getContent();
        $json = json_decode($raw);

        $this->assertEquals(count($task_manager->getTasks()), count(get_object_vars($json->tasks)));
    }

    public function testRouteTaskManager_task_create() {
        $task_manager = new \task_manager(self::$DI['app']);

        $nTasks0 = count($task_manager->getTasks());

        self::$DI['client']->request(
                'POST', '/admin/task-manager/tasks/create/', array('tcl' => 'task_period_test')
        );

        $nTasks1 = count($task_manager->getTasks(true));  // true: force refresh
        $this->assertEquals($nTasks1, $nTasks0 + 1);
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect());

        $location = self::$DI['client']->getResponse()->headers->get('location');
        $tid = array_pop(explode('/', $location));

        self::$DI['client']->request(
                'GET', '/admin/task-manager/task/' . $tid . '/log', array()
        );

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request(
                'GET', '/admin/task-manager/task/' . $tid . '/delete', array()
        );

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect('/admin/task-manager/tasks/'));
        $nTasks2 = count($task_manager->getTasks(true));   // true: force refresh
        $this->assertEquals($nTasks2, $nTasks0);
    }

    public function testRouteTaskManager_scheduler_log() {
        $task_manager = new \task_manager(self::$DI['app']);

        self::$DI['client']->request(
                'GET', '/admin/task-manager/scheduler/log', array()
        );

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

}
