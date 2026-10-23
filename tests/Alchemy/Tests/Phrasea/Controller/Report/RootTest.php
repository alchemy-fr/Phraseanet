<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    private $dmin;
    private $dmax;

    public function setUp(): void
    {
        parent::setUp();

        $this->dmax = new \DateTime('now');
        $this->dmin = new \DateTime('-1 month');
    }

    public function testRouteDashboard()
    {
        $this->authenticate(self::$DI['app']);

        self::$DI['client']->request('GET', '/report/dashboard');

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
    }
}
