<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Facebook;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Facebook\Authentication\AccessToken;

/**
 * @group functional
 * @group legacy
 */
class FacebookTest extends ProviderTestCase
{
    const TOKEN = 'aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789';

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
        return [
            [$provider, $this->getRequestMock()]
        ];
    }

    public function getProviderForLogout()
    {
        $this->markTestSkipped('Skipping because \Facebook runs session_start');
    }

    public function provideDataForSuccessCallback()
    {
        $provider = $this->getProvider();
        $facebookMock = $this->getFacebookMock(true);
        $provider->setFacebook($facebookMock);
        return [
            [$provider, $this->getRequestMock()]
        ];
    }

    protected function getProvider()
    {
        return new Facebook($this->getUrlGeneratorMock(), $this->getMockSession(), $this->getFacebookMock());
    }

    protected function authenticateProvider(ProviderInterface $provider)
    {
        $facebookMock = $this->getFacebookMock(true);
        $provider->setFacebook($facebookMock);
        $provider->getSession()->set('fb_access_token', self::TOKEN);
    }

    protected function getProviderForSuccessIdentity()
    {
        $provider = $this->getProvider();
        $this->authenticateProvider($provider);

        return $provider;
    }

    protected function getProviderForFailingIdentity()
    {
        return $this->getProvider();
    }

    protected function getAvailableFieldsForIdentity()
    {
        return [
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_USERNAME  => self::FIRSTNAME,
            Identity::PROPERTY_FIRSTNAME => self::FIRSTNAME,
            Identity::PROPERTY_LASTNAME  => self::LASTNAME,
            Identity::PROPERTY_EMAIL     => self::EMAIL,
        ];
    }

    protected function getTestOptions()
    {
        return [
            'app-id' => 'zizi',
            'secret' => 's3cr3t',
            'default-graph-version' => 'v2.10'
        ];
    }

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }

    private function getFacebookMock($ValidAccessToken = false)
    {
        $facebook = $this->getMockBuilder('Facebook\Facebook')
            ->disableOriginalConstructor()
            ->setMethods(['getRedirectLoginHelper', 'get', 'getOAuth2Client'])
            ->getMock();

        $helper = $this->getFacebookRedirectLoginHelperMock($ValidAccessToken);

        $facebook->expects($this->any())
            ->method('getRedirectLoginHelper')
            ->will($this->returnValue($helper));

        $OAuth2Client = $this->getOAuth2ClientMock();

        $facebook->expects($this->any())
            ->method('getOAuth2Client')
            ->will($this->returnValue($OAuth2Client));

        if ($ValidAccessToken)
        {
            $FacebookResponse = $this->getFacebookResponseMock();

            $facebook->expects($this->any())
                ->method('get')
                ->will($this->returnValue($FacebookResponse));
        }

        return $facebook;
    }

    private function getAccessTokenMock($valid = true)
    {
        $expiresAt = (time() + 3600);
        return ($valid)? new AccessToken(self::TOKEN, $expiresAt) : null;
    }

    private function getFacebookRedirectLoginHelperMock($ValidAccessToken = true)
    {
        $accessToken = $this->getAccessTokenMock($ValidAccessToken);

        $helper = $this->getMockBuilder('Facebook\Helpers\FacebookRedirectLoginHelper')
            ->disableOriginalConstructor()
            ->setMethods(['getLoginUrl', 'getAccessToken', 'getError'])
            ->getMock();

        $helper->expects($this->any())
            ->method('getLoginUrl')
            ->will($this->returnValue('http://www.facebook.com/'));

        $helper->expects($this->any())
            ->method('getAccessToken')
            ->will($this->returnValue($accessToken));

        $helper->expects($this->any())
            ->method('getError')
            ->will($this->returnValue(null));

        return $helper;
    }

    private function getOAuth2ClientMock()
    {
        $OAuth2Client = $this->getMockBuilder('Facebook\Authentication\OAuth2Client')
            ->disableOriginalConstructor()
            ->setMethods(['getLongLivedAccessToken'])
            ->getMock();

        $OAuth2Client->expects($this->any())
            ->method('getLongLivedAccessToken')
            ->will($this->returnValue(self::TOKEN));

        return $OAuth2Client;
    }

    private function getFacebookResponseMock()
    {
        $FacebookResponse = $this->getMockBuilder('Facebook\FacebookResponse')
            ->disableOriginalConstructor()
            ->setMethods(['getGraphUser'])
            ->getMock();

        $FacebookResponse->expects($this->any())
            ->method('getGraphUser')
            ->will($this->returnValue([
                'id'    => self::ID,
                'name'   => self::FIRSTNAME,
                'first_name' => self::FIRSTNAME,
                'last_name'  => self::LASTNAME,
                'email'      => self::EMAIL,
                'picture'   => self::IMAGEURL
            ]));

        return $FacebookResponse;
    }

}
