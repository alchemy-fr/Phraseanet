<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class MustacheLoaderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function testRouteSlash()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testRouteSlashWrongUrl()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/', array('template' => '/../../../../config/config.yml'));

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testRouteSlashWrongFile()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/', array('template' => 'patator_lala'));

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    public function testRouteGood()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/', array('template' => 'Feedback-Badge'));

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->isOk());
    }
}
