<?php

namespace Alchemy\Tests\Phrasea\Controller\Utils;

class ControllerConnectionTestTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     * Default route test
     */
    public function testRouteMysql()
    {
        $connexion = self::$DI['app']['configuration']['main']['database'];

        $params = [
            "hostname" => $connexion['host'],
            "port"     => $connexion['port'],
            "user"     => $connexion['user'],
            "password" => $connexion['password'],
            "dbname"   => $connexion['dbname'],
        ];

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testRouteMysqlFailed()
    {
        $connexion = self::$DI['app']['configuration']['main']['database'];

        $params = [
            "hostname" => $connexion['host'],
            "port"     => $connexion['port'],
            "user"     => $connexion['user'],
            "password" => "fakepassword",
            "dbname"   => $connexion['dbname'],
        ];

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
        $connexion = self::$DI['app']['configuration']['main']['database'];

        $params = [
            "hostname" => $connexion['host'],
            "port"     => $connexion['port'],
            "user"     => $connexion['user'],
            "password" => $connexion['password'],
            "dbname"   => "fake-DTABASE-name"
        ];

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
