<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Facebook;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use JanuSoftware\Facebook\Authentication\AccessToken;
use JanuSoftware\Facebook\Authentication\OAuth2Client;
use JanuSoftware\Facebook\Exception\SDKException;
use JanuSoftware\Facebook\Facebook as FacebookSdk;
use JanuSoftware\Facebook\Helper\RedirectLoginHelper;
use JanuSoftware\Facebook\Response;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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
        $facebook = $this->createMock(FacebookSdk::class);

        $helper = $this->getFacebookRedirectLoginHelperMock($ValidAccessToken);

        $facebook
            ->method('getRedirectLoginHelper')
            ->willReturn($helper);

        $OAuth2Client = $this->getOAuth2ClientMock();

        $facebook
            ->method('getOAuth2Client')
            ->willReturn($OAuth2Client);

        if ($ValidAccessToken)
        {
            $FacebookResponse = $this->getFacebookResponseMock();

            $facebook
                ->method('get')
                ->willReturn($FacebookResponse);
        } else {
            $facebook
                ->method('get')
                ->willThrowException(new SDKException('You must provide an access token.'));
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

        $helper = $this->createMock(RedirectLoginHelper::class);

        $helper
            ->method('getLoginUrl')
            ->willReturn('http://www.facebook.com/');

        $helper
            ->method('getAccessToken')
            ->willReturn($accessToken);

        $helper
            ->method('getError')
            ->willReturn(null);

        return $helper;
    }

    private function getOAuth2ClientMock()
    {
        $OAuth2Client = $this->createMock(OAuth2Client::class);

        $OAuth2Client
            ->method('getLongLivedAccessToken')
            ->willReturn(new AccessToken(self::TOKEN, time() + 60 * 24 * 3600));

        return $OAuth2Client;
    }

    private function getFacebookResponseMock()
    {
        $FacebookResponse = $this->createMock(Response::class);

        $FacebookResponse
            ->method('getDecodedBody')
            ->willReturn([
                'id'    => self::ID,
                'name'   => self::FIRSTNAME,
                'first_name' => self::FIRSTNAME,
                'last_name'  => self::LASTNAME,
                'email'      => self::EMAIL,
                'picture'   => ['data' => ['url' => self::IMAGEURL]],
            ]);

        return $FacebookResponse;
    }

}
