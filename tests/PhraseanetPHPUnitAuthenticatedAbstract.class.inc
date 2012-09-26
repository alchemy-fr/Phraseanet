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

        self::$DI['app']['session']->clear();
        self::$DI['app']['session']->set('usr_id', self::$DI['user']->get_id());
    }

    public function tearDown()
    {
        self::$DI['app']['session']->clear();
        parent::tearDown();
    }
}
