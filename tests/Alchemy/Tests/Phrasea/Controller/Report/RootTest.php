<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class RootTest extends \PhraseanetAuthenticatedWebTestCase
{
    private $dmin;
    private $dmax;

    public function __construct()
    {
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
