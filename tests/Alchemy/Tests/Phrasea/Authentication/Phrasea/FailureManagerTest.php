<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Alchemy\Phrasea\Model\Repositories\AuthFailureRepository;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\HttpFoundation\Request;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FailureManagerTest extends \PhraseanetTestCase
{
    public function testSaveFailure()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(null);

        $ip = '192.168.16.178';
        $username = 'romainneutron';

        $request = $this->getRequestMock();
        $request
            ->method('getClientIp')
            ->will($this->returnValue($ip));

        $oldFailures = [
            $this->createStub(AuthFailure::class),
            $this->createStub(AuthFailure::class)
        ];

        $repo->expects($this->once())
            ->method('findOldFailures')
            ->will($this->returnValue($oldFailures));

        $em->expects($this->exactly(count($oldFailures)))
            ->method('remove')
            ->with($this->isInstanceOf(AuthFailure::class));

        $catchFailure = null;
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AuthFailure::class))
            ->will($this->returnCallback(function ($failure) use (&$catchFailure) {
                $catchFailure = $failure;
            }));

        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->saveFailure($username, $request);

        /** @var null|AuthFailure $catchFailure */
        $this->assertInstanceOf(AuthFailure::class, $catchFailure);
        $this->assertEquals($ip, $catchFailure->getIp());
        $this->assertEquals(true, $catchFailure->getLocked());
        $this->assertEquals($username, $catchFailure->getUsername());
    }

    public function testCheckFailures()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $oldFailures = [];

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresLessThan9()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 8);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresMoreThan9WithoutCaptcha()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(false);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $this->expectException(RequireCaptchaException::class);
        $this->expectExceptionMessage("Too many failures, require captcha");
        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresMoreThan9WithCorrectCaptcha()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, true);

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $this->expectException(RequireCaptchaException::class);
        $this->expectExceptionMessage("Too many failures, require captcha");

        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresMoreThan9WithIncorrectCaptcha()
    {
        $this->expectException(\Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException::class);
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, false);

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($repo, $em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresTrialsIsConfigurableUnderThreshold()
    {
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 2);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $this->expectException(RequireCaptchaException::class);
        $this->expectExceptionMessage("Too many failures, require captcha");
        $manager = new FailureManager($repo, $em, $recaptcha, 2);
        $manager->checkFailures($username, $request);
    }

    public function testTrialsIsConfigurable()
    {
        $em = $this->createEntityManagerMock();
        $recaptcha = $this->getReCaptchaMock(null);

        $manager = new FailureManager($this->createAuthFailureRepositoryMock(), $em, $recaptcha, 2);
        $this->assertEquals(2, $manager->getTrials());
    }

    public function testCheckFailuresTrialsIsConfigurableOverThreshold()
    {
        $this->expectException(\Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException::class);
        $repo = $this->createAuthFailureRepositoryMock();
        $em = $this->createEntityManagerMock();
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, false);

        $username = 'romainneutron';

        $oldFailures = $this->ArrayIze(function () {
            return $this->createMock(AuthFailure::class);
        }, 3);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($repo, $em, $recaptcha, 2);
        $manager->checkFailures($username, $request);
    }

    public function testFailureOlderThan2MonthsAreRemovedOnFailure()
    {
        self::$DI['app']['orm.em']->getEventManager()->removeEventSubscriber(new TimestampableListener());
        $recaptcha = $this->getReCaptchaMock(null);

        $ip = '192.168.16.178';
        $username = 'romainneutron';

        $request = $this->getRequestMock();
        $request
            ->method('getClientIp')
            ->will($this->returnValue($ip));

        $this->assertCount(10, self::$DI['app']['orm.em']->getRepository('Phraseanet:AuthFailure')
                ->findOldFailures());
        $this->assertCount(12, self::$DI['app']['orm.em']->getRepository('Phraseanet:AuthFailure')
                ->findAll());

        $manager = new FailureManager(self::$DI['app']['repo.auth-failures'], self::$DI['app']['orm.em'], $recaptcha, 9);
        $manager->saveFailure($username, $request);

        $this->assertCount(0, self::$DI['app']['orm.em']->getRepository('Phraseanet:AuthFailure')
                ->findOldFailures());
        $this->assertCount(3, self::$DI['app']['orm.em']->getRepository('Phraseanet:AuthFailure')
                ->findAll());

        self::$DI['app']['orm.em']->getEventManager()->addEventSubscriber(new TimestampableListener());
    }

    private function ArrayIze($failure, $n)
    {
        $failures = [];

        for ($i = 0; $i != $n; $i++) {
            $failures[] = $failure();
        }

        return $failures;
    }

    private function getReCaptchaMock($isSetup = true, ?Request $request = null, $isValid = false)
    {
        return $this->createMock('ReCaptcha\ReCaptcha');
    }

    private function getRequestMock()
    {
        return $this->createMock(Request::class);
    }

    /**
     * @return AuthFailureRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createAuthFailureRepositoryMock()
    {
        return $this->createMock(AuthFailureRepository::class);
    }
}
