<?php

namespace Alchemy\Tests\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Phrasea\FailureManager;
use Symfony\Component\HttpFoundation\Request;

class FailureManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::saveFailure
     */
    public function testSaveFailure()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(null);

        $ip = '192.168.16.178';
        $username = 'romainneutron';

        $request = $this->getRequestMock();
        $request->expects($this->any())
            ->method('getClientIp')
            ->will($this->returnValue($ip));

        $oldFailures = array(
            $this->getMock('Entities\AuthFailure'),
            $this->getMock('Entities\AuthFailure')
        );

        $repo->expects($this->once())
            ->method('findOldFailures')
            ->will($this->returnValue($oldFailures));

        $em->expects($this->exactly(count($oldFailures)))
            ->method('remove')
            ->with($this->isInstanceOf('Entities\AuthFailure'));

        $catchFailure = null;
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Entities\AuthFailure'))
            ->will($this->returnCallback(function ($failure) use (&$catchFailure) {
                $catchFailure = $failure;
            }));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->saveFailure($username, $request);

        $this->assertEquals($ip, $catchFailure->getIp());
        $this->assertEquals(true, $catchFailure->getLocked());
        $this->assertEquals($username, $catchFailure->getUsername());
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailures()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $oldFailures = array();

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailuresLessThan9()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            return $phpunit->getMock('Entities\AuthFailure');
        }, 8);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailuresMoreThan9WithoutCaptcha()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(false);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            return $phpunit->getMock('Entities\AuthFailure');
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    /**
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailuresMoreThan9WithCorrectCaptcha()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, true);

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            $failure = $phpunit->getMock('Entities\AuthFailure');
            $failure->expects($phpunit->once())
                ->method('setLocked')
                ->with($phpunit->equalTo(false));
            return $failure;
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    /**
     * @expectedException Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailuresMoreThan9WithIncorrectCaptcha()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, false);

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            return $phpunit->getMock('Entities\AuthFailure');
        }, 10);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 9);
        $manager->checkFailures($username, $request);
    }

    public function testCheckFailuresTrialsIsConfigurableUnderThreshold()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(null);
        $request = $this->getRequestMock();

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            return $phpunit->getMock('Entities\AuthFailure');
        }, 2);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 2);
        $manager->checkFailures($username, $request);
    }

    public function testTrialsIsConfigurable()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $recaptcha = $this->getReCaptchaMock(null);

        $manager = new FailureManager($em, $recaptcha, 2);
        $this->assertEquals(2, $manager->getTrials());
    }

    /**
     * @expectedException Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException
     * @covers Alchemy\Phrasea\Authentication\Phrasea\FailureManager::checkFailures
     */
    public function testCheckFailuresTrialsIsConfigurableOverThreshold()
    {
        $repo = $this->getRepo();
        $em = $this->getEntityManagerMock($repo);
        $request = $this->getRequestMock();
        $recaptcha = $this->getReCaptchaMock(true, $request, false);

        $username = 'romainneutron';

        $phpunit = $this;
        $oldFailures = $this->ArrayIze(function () use ($phpunit) {
            return $phpunit->getMock('Entities\AuthFailure');
        }, 3);

        $repo->expects($this->once())
            ->method('findLockedFailuresMatching')
            ->will($this->returnValue($oldFailures));

        $manager = new FailureManager($em, $recaptcha, 2);
        $manager->checkFailures($username, $request);
    }

    private function ArrayIze($failure, $n)
    {
        $failures = array();

        for ($i = 0; $i != $n; $i++) {
            $failures[] = $failure();
        }

        return $failures;
    }

    private function getEntityManagerMock($repo)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Entities\AuthFailure'))
            ->will($this->returnValue($repo));

        return $em;
    }

    private function getReCaptchaMock($isSetup = true, Request $request = null, $isValid = false)
    {
        $recaptcha = $this->getMockBuilder('Neutron\ReCaptcha\ReCaptcha')
            ->disableOriginalConstructor()
            ->getMock();

        if ($request) {
            $response = $this->getMockBuilder('Neutron\ReCaptcha\Response')
                ->disableOriginalConstructor()
                ->getMock();

            $response->expects($this->once())
                ->method('isValid')
                ->will($this->returnValue($isValid));

            $recaptcha->expects($this->once())
                ->method('bind')
                ->with($this->equalTo($request))
                ->will($this->returnValue($response));
        }

        if (null !== $isSetup) {
            $recaptcha->expects($this->once())
                ->method('isSetup')
                ->will($this->returnValue($isSetup));
        }

        return $recaptcha;
    }

    private function getRequestMock()
    {
        return $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getRepo()
    {
        return $this->getMockBuilder('Repositories\AuthFailureRepository')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
