<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
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
