<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetPHPUnitAuthenticatedAbstract extends PhraseanetPHPUnitAbstract
{

    public function setUp()
    {
        parent::setUp();
        $this->authenticate(self::$DI['app']);
    }

    public function tearDown()
    {
        $this->logout(self::$DI['app']);
        parent::tearDown();
    }
}
