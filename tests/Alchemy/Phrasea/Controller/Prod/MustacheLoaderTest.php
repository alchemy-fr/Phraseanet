<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class MustacheLoaderTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;
    protected static $useExceptionHandler = false;

    public function testRouteSlash()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/');

        $response = self::$DI['client']->getResponse();
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRouteSlashWrongUrl()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/', array('template' => '/../../../../config/config.yml'));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        /* @var $response \Symfony\Component\HttpFoundation\Response */
    }

    public function testRouteSlashWrongFile()
    {
        self::$DI['client']->request('GET', '/prod/MustacheLoader/', array('template' => 'patator_lala'));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
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
