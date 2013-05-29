<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Facebook;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

class FacebookTest extends ProviderTestCase
{
    public function testGetSetSession()
    {
        $this->markTestSkipped('testGetSetSession disabled for facebook');
    }

    public function testGetSetGuzzleClient()
    {
        $this->markTestSkipped('testGetSetGuzzleClient disabled for facebook');
    }

    public function testIsBuiltWithFactory()
    {
        $this->markTestSkipped('Skipping because \Facebook runs session_start');
    }

    public function testCreate()
    {
        $this->markTestSkipped('Skipping because \Facebook runs session_start');
    }

    public function provideDataForFailingCallback()
    {
        $provider = $this->getProvider();
        $provider->getFacebook()->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(null));

        return array(
            array($provider, $this->getRequestMock())
        );
    }

    public function getProviderForLogout()
    {
        $this->markTestSkipped('Skipping because \Facebook runs session_start');
    }

    public function provideDataForSuccessCallback()
    {
        $provider = $this->getProvider();
        $provider->getFacebook()->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('123456'));

        return array(
            array($provider, $this->getRequestMock())
        );
    }

    protected function getProvider()
    {
        return new Facebook($this->getFacebookMock(), $this->getUrlGeneratorMock());
    }

    protected function authenticate(ProviderInterface $provider)
    {
        $provider->getFacebook()->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('123456'));
    }

    protected function getProviderForSuccessIdentity()
    {
        $provider = $this->getProvider();
        $this->authenticate($provider);

        $facebook = $this->getMockBuilder('Facebook')
            ->disableOriginalConstructor()
            ->setMethods(array('getLoginUrl', 'api', 'getUser'))
            ->getMock();

        $facebook->expects($this->any())
            ->method('getLoginUrl')
            ->will($this->returnValue('http://www.facebook.com/'));

        $facebook->expects($this->once())
            ->method('api')
            ->will($this->returnValue(array(
               'id'         => self::ID,
               'username'   => self::FIRSTNAME,
               'first_name' => self::FIRSTNAME,
               'last_name'  => self::LASTNAME,
               'email'      => self::EMAIL,
            )));

        $provider->setFacebook($facebook);

        return $provider;
    }

    protected function getProviderForFailingIdentity()
    {
        return $this->getProvider();
    }

    protected function getAvailableFieldsForIdentity()
    {
        return array(
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_USERNAME  => self::FIRSTNAME,
            Identity::PROPERTY_FIRSTNAME => self::FIRSTNAME,
            Identity::PROPERTY_LASTNAME  => self::LASTNAME,
            Identity::PROPERTY_EMAIL     => self::EMAIL,
        );
    }

    protected function getTestOptions()
    {
        return array(
            'app-id' => 'zizi',
            'secret' => 's3cr3t',
        );
    }

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }

    private function getFacebookMock()
    {
        $facebook = $this->getMockBuilder('Facebook')
            ->disableOriginalConstructor()
            ->setMethods(array('getLoginUrl', 'api', 'getUser'))
            ->getMock();

        $facebook->expects($this->any())
            ->method('getLoginUrl')
            ->will($this->returnValue('http://www.facebook.com/'));

        $facebook->expects($this->any())
            ->method('api')
            ->will($this->returnCallback(function () use ($facebook) {
                if (!$facebook->getUser()) {
                    throw new \FacebookApiException(array(
                        'error_msg' => 'Not authenticated'
                    ));
                }
            }));

        return $facebook;
    }
}
