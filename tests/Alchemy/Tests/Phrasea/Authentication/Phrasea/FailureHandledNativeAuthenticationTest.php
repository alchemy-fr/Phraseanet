<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\FailureHandledNativeAuthentication;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FailureHandledNativeAuthenticationTest extends \PhraseanetTestCase
{
    public function testGetUsrIdWhenSuccessful()
    {
        $manager = $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\FailureManager');

        $auth = $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface');

        $request = $this->createStub('Symfony\Component\HttpFoundation\Request');

        $username = 'droopy';
        $password = 'gloups';

        $auth->expects($this->once())
            ->method('getUsrId')
            ->with($username, $password, $request)
            ->will($this->returnValue(42));

        $manager->expects($this->once())
            ->method('checkFailures')
            ->with($username, $request);

        $manager->expects($this->never())
            ->method('saveFailure');

        $failure = new FailureHandledNativeAuthentication($auth, $manager);
        $this->assertEquals(42, $failure->getUsrId($username, $password, $request));
    }

    public function testGetUsrIdWhenNotSuccessful()
    {
        $manager = $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\FailureManager');

        $auth = $this->createMock('Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface');

        $request = $this->createStub('Symfony\Component\HttpFoundation\Request');

        $username = 'droopy';
        $password = 'gloups';

        $auth->expects($this->once())
            ->method('getUsrId')
            ->with($username, $password, $request)
            ->will($this->returnValue(null));

        $manager->expects($this->at(0))
            ->method('checkFailures')
            ->with($username, $request);

        $manager->expects($this->at(1))
            ->method('saveFailure')
            ->with($username, $request);

        $manager->expects($this->at(2))
            ->method('checkFailures')
            ->with($username, $request);

        $failure = new FailureHandledNativeAuthentication($auth, $manager);
        $this->assertNull($failure->getUsrId($username, $password, $request));
    }
}
