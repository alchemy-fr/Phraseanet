<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

class ControllerConnectionTestTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     * As controllers use WebTestCase, it requires a client
     */
    protected $client;

    /**
     * The application loader
     */
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
    }

    /**
     * Default route test
     */
    public function testRouteMysql()
    {
        $configuration = \Alchemy\Phrasea\Core\Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => $connexion->get('password'),
            "dbname"   => $connexion->get('dbname')
        );

        $this->client->request("GET", "/tests/connection/mysql/", $params);
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteMysqlFailed()
    {
        $configuration = \Alchemy\Phrasea\Core\Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => "fakepassword",
            "dbname"   => $connexion->get('dbname')
        );

        $this->client->request("GET", "/tests/connection/mysql/", $params);
        $response = $this->client->getResponse();
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $this->assertTrue($response->isOk());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('connection', $content);
        $this->assertObjectHasAttribute('database', $content);
        $this->assertObjectHasAttribute('is_empty', $content);
        $this->assertObjectHasAttribute('is_appbox', $content);
        $this->assertObjectHasAttribute('is_databox', $content);
        $this->assertFalse($content->connection);
    }

    public function testRouteMysqlDbFailed()
    {
        $configuration = \Alchemy\Phrasea\Core\Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => $connexion->get('password'),
            "dbname"   => "fake-DTABASE-name"
        );

        $this->client->request("GET", "/tests/connection/mysql/", $params);
        $response = $this->client->getResponse();
        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $this->assertTrue($response->isOk());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('connection', $content);
        $this->assertObjectHasAttribute('database', $content);
        $this->assertObjectHasAttribute('is_empty', $content);
        $this->assertObjectHasAttribute('is_appbox', $content);
        $this->assertObjectHasAttribute('is_databox', $content);
        $this->assertFalse($content->database);
    }
}

