<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 */
class LanguageTest extends \PhraseanetAuthenticatedTestCase
{
    protected $client;

    public function testRootPost()
    {
        self::$DI['client']->request("GET", '/prod/language/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
    }
}
