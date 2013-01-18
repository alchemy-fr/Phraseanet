<?php

namespace Alchemy\Tests\Phrasea\Controller\Utils;

use Alchemy\Phrasea\Core\Configuration;

class ControllerConnectionTestTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     * Default route test
     */
    public function testRouteMysql()
    {
        $configuration = Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => $connexion->get('password'),
            "dbname"   => $connexion->get('dbname')
        );

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteMysqlFailed()
    {
        $configuration = Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => "fakepassword",
            "dbname"   => $connexion->get('dbname')
        );

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
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
        $configuration = Configuration::build();

        $chooseConnexion = $configuration->getPhraseanet()->get('database');

        $connexion = $configuration->getConnexion($chooseConnexion);

        $params = array(
            "hostname" => $connexion->get('host'),
            "port"     => $connexion->get('port'),
            "user"     => $connexion->get('user'),
            "password" => $connexion->get('password'),
            "dbname"   => "fake-DTABASE-name"
        );

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
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

