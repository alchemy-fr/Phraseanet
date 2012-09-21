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
        $auth = new Session_Authentication_None(self::$user);
        self::$application->openAccount($auth);
    }

    public function tearDown()
    {
        self::$application->closeAccount();
        parent::tearDown();
    }
}
