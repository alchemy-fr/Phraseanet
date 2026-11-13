<?php

use Alchemy\Phrasea\Authentication\ACLProvider;

abstract class PhraseanetAuthenticatedTestCase extends \PhraseanetTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $stubbedACL;

    public function setUp(): void
    {
        parent::setUp();
        $this->authenticate(self::$DI['app']);
    }

    public function tearDown(): void
    {
        $this->logout(self::$DI['app']);
        parent::tearDown();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function stubACL()
    {
        $stubbedACL = $this->createMock('\ACL');

        $aclProvider = $this->createMock(ACLProvider::class);

        $aclProvider
            ->method('get')
            ->will($this->returnValue($stubbedACL));

        $app = $this->getApplication();
        $app['acl'] = $aclProvider;
        $app->setAclProvider($aclProvider);

        return $stubbedACL;
    }
}
