<?php

use Alchemy\Phrasea\Authentication\ACLProvider;

abstract class PhraseanetAuthenticatedTestCase extends \PhraseanetTestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject */
    protected $stubbedACL;

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

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function stubACL()
    {
        $stubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();

        $aclProvider = $this->getMockBuilder(ACLProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($stubbedACL));

        $app = $this->getApplication();
        $app['acl'] = $aclProvider;
        $app->setAclProvider($aclProvider);

        return $stubbedACL;
    }
}
