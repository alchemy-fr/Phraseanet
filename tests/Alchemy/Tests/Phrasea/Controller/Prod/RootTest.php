<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        self::$DI['client']->request('GET', '/prod/');

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertEquals('UTF-8', $response->getCharset());
    }
}
