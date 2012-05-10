<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetWebTestCaseAuthenticatedAbstract extends PhraseanetPHPUnitAuthenticatedAbstract
{

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $auth = new Session_Authentication_None(self::$user);
        $session->authenticate($auth);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();
        $session->logout();
    }
}
