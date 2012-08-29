<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetWebTestCaseAuthenticatedAbstract extends PhraseanetPHPUnitAuthenticatedAbstract
{
    protected $StubbedACL;

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

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->createClient();
        $this->StubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function setAdmin($bool)
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')
            ->setMethods(array('is_admin', 'ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $stubAuthenticatedUser->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_base')
            ->will($this->returnValue($bool));

        $stubAuthenticatedUser->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($this->StubbedACL));

        $stubCore = $this->getMockBuilder('\Alchemy\Phrasea\Core')
            ->setMethods(array('getAuthenticatedUser'))
            ->getMock();

        $stubCore->expects($this->any())
            ->method('getAuthenticatedUser')
            ->will($this->returnValue($stubAuthenticatedUser));

        $this->app['phraseanet.core'] = $stubCore;
    }
}
