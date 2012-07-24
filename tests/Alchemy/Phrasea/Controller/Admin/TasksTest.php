<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class TasksTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected $appbox;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Admin.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->appbox = appbox::get_instance(\bootstrap::getCore());
    }

    /**
     * Default route test
     */
    public function testRouteTasks()
    {
        $task_manager = new \task_manager($this->appbox);

        /**
         * get /admin/tasks/ should return html by default
         */
        $crawler = $this->client->request('GET', '/tasks/', array());
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('form#taskManagerForm'));

        /**
         * get /admin/tasks/ can also return json
         */
        $crawler = $this->client->request(
            'GET',
            '/tasks/',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json')
            );
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertTrue($this->client->getResponse()->headers->contains('Content-Type', 'application/json'));

        $raw = $this->client->getResponse()->getContent();
        $json = json_decode($raw);

        $this->assertEquals(count($task_manager->getTasks()), count(get_object_vars($json->tasks)));
    }
}
