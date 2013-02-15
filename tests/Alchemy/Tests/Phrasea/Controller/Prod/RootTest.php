<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class ControllerRootTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    /**
     * Default route test
     */
    public function testRouteSlash()
    {
        self::$DI['app']['authentication']->openAccount(self::$DI['user']);

        $crawler = self::$DI['client']->request('GET', '/prod/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('UTF-8', $response->getCharset());
    }
}
