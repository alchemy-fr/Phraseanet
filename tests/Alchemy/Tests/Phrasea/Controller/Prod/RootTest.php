<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $response = $this->request('GET', '/prod/');

        $this->assertTrue($response->isOk());
        $this->assertEquals('UTF-8', $response->getCharset());
    }
}
