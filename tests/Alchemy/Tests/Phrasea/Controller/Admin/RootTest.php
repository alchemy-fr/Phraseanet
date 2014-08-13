<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        $this->authenticate(self::$DI['app']);

        self::$DI['client']->request('GET', '/admin/', ['section' => 'base:featured']);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        self::$DI['client']->request('GET', '/admin/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function testRouteMysql()
    {
        $connexion = self::$DI['app']['phraseanet.configuration']['main']['database'];

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
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('connection', $content);
        $this->assertObjectHasAttribute('database', $content);
        $this->assertObjectHasAttribute('is_empty', $content);
        $this->assertObjectHasAttribute('is_appbox', $content);
        $this->assertObjectHasAttribute('is_databox', $content);
        $this->assertTrue($content->connection);
    }

    public function testRouteMysqlFailed()
    {
        $connexion = self::$DI['app']['phraseanet.configuration']['main']['database'];
        $params = [
            "hostname" => $connexion['host'],
            "port"     => $connexion['port'],
            "user"     => $connexion['user'] . 'fake',
            "password" => $connexion['password'],
            "dbname"   => $connexion['dbname'],
        ];

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $content = json_decode($response->getContent());
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
        $connexion = self::$DI['app']['phraseanet.configuration']['main']['database'];

        $params = [
            "hostname" => $connexion['host'],
            "port"     => $connexion['port'],
            "user"     => $connexion['user'],
            "password" => $connexion['password'],
            "dbname"   => "fake-database-name"
        ];

        self::$DI['client']->request("GET", "/admin/tests/connection/mysql/", $params);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $content = json_decode($response->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('connection', $content);
        $this->assertObjectHasAttribute('database', $content);
        $this->assertObjectHasAttribute('is_empty', $content);
        $this->assertObjectHasAttribute('is_appbox', $content);
        $this->assertObjectHasAttribute('is_databox', $content);
        $this->assertTrue($content->connection);
        $this->assertFalse($content->database);
    }

    /**
     * Default route test
     */
    public function testRoutePath()
    {
        $file = new \SplFileObject(__DIR__ . '/../../../../../files/cestlafete.jpg');
        self::$DI['client']->request("GET", "/admin/tests/pathurl/path/", ['path' => $file->getPathname()]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($content));
        $this->assertObjectHasAttribute('exists', $content);
        $this->assertObjectHasAttribute('file', $content);
        $this->assertObjectHasAttribute('dir', $content);
        $this->assertObjectHasAttribute('readable', $content);
        $this->assertObjectHasAttribute('executable', $content);
    }

    public function testRouteUrl()
    {
        self::$DI['client']->request("GET", "/admin/tests/pathurl/url/", ['url' => "www.google.com"]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($content));
    }
}
