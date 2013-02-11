<?php

namespace Alchemy\Tests\Phrasea\Controller\Utils;

class ControllerPathFileTestTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     * Default route test
     */
    public function testRoutePath()
    {
        $file = new \SplFileObject(__DIR__ . '/../../../../../files/cestlafete.jpg');
        self::$DI['client']->request("GET", "/admin/tests/pathurl/path/", array('path' => $file->getPathname()));

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
        self::$DI['client']->request("GET", "/admin/tests/pathurl/url/", array('url' => "www.google.com"));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $content = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($content));
    }
}
