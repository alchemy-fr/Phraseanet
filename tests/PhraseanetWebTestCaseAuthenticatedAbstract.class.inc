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
        $appbox = self::$application['phraseanet.appbox'];
        $session = $appbox->get_session();
        $auth = new Session_Authentication_None(self::$user);
        $session->authenticate($auth);
    }

    public static function tearDownAfterClass()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $session = $appbox->get_session();
        $session->logout();
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();

        $this->StubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setAdmin($bool)
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')//, array('is_admin', 'ACL'), array(self::$application['phraseanet.user']->get_id(), self::$application))
            ->setMethods(array('ACL'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->StubbedACL->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('give_access_to_sbas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('update_rights_to_sbas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('update_rights_to_bas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_base')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_sbas')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('get_granted_base')
            ->will($this->returnValue(array(self::$collection)));

        $this->StubbedACL->expects($this->any())
            ->method('get_granted_sbas')
            ->will($this->returnValue(array(self::$collection->get_databox())));

        $stubAuthenticatedUser->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($this->StubbedACL));

        self::$application['phraseanet.user'] = $stubAuthenticatedUser;

        $this->client = new Client(self::$application, array());
    }
}
