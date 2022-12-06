<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\GooglePlus;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

/**
 * @group functional
 * @group legacy
 */
class GooglePlusTest extends ProviderTestCase
{
    public function testGetSetGoogleClient()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf('Google_client', $provider->getGoogleClient());
        $google = $this->getMockBuilder('Google_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->setGoogleClient($google);
        $this->assertEquals($google, $provider->getGoogleClient());
    }

    public function provideDataForFailingCallback()
    {
        $provider = $this->getProvider();

        $state = md5(mt_rand());
        $provider->getSession()->set('google-plus.provider.state', $state . mt_rand());

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        return [
            [$this->provideFailingProvider(), $this->getRequestMock()],
            [$provider, $request],
        ];
    }

    public function getProviderForLogout()
    {
        $provider = $this->getProvider();

        $provider->getGoogleClient()
            ->expects($this->once())
            ->method('revokeToken');

        return $provider;
    }

    public function provideDataForSuccessCallback()
    {
        $provider = $this->getProvider();

        $guzzle = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $requestPost = $this->getMock('Guzzle\Http\Message\EntityEnclosingRequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $requestGet->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $requestPost->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $guzzle->expects($this->any())
            ->method('post')
            ->will($this->returnValue($requestPost));

        $provider->setGuzzleClient($guzzle);

        $state = md5(mt_rand());
        $provider->getSession()->set('google-plus.provider.state', $state);

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, [
            'state' => $state,
        ]);

        $ticket = $this->getMockBuilder('Google_LoginTicket')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->getGoogleClient()->expects($this->once())
            ->method('verifyIdToken')
            ->will($this->returnValue($ticket));

        return [
            [$provider, $request],
        ];
    }

    public function getTestOptions()
    {
        return [
            'client-id'     => 'google-plus-client-id',
            'client-secret' => 'google-plus-client-secret',
        ];
    }

    protected function getProviderForSuccessIdentity()
    {
        $provider = $this->getProvider();

        $guzzle = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $requestGet->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $response->expects($this->any())
            ->method('getBody')
            ->with($this->equalTo(true))
            ->will($this->returnValue(json_encode([
                'email' => self::EMAIL
            ])));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);
        $provider->getSession()->set('google-plus.provider.id', '12345678');

        $googleClient = $this->getMockBuilder('\Google_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $googleClient->expects($this->any())
            ->method("getAccessToken")
            ->will($this->returnValue(
                json_encode([
                    'access_token' => 'fakeAccessToken',
                    'expires_in'   => 3599,
                    'id_token'     => 'fakeIdToken',
                    'token_type'   => 'Bearer',
                    'created'      => 1511374176,
                ])
            ));

        $googleClient->expects($this->any())
            ->method("verifyIdToken")
            ->with($this->equalTo("fakeIdToken"))
            ->will($this->returnValue(
                [
                    'azp'            => '1234azerty.apps.googleusercontent.com',
                    'aud'            => '1234azerty.apps.googleusercontent.com',
                    'sub'            => self::ID,
                    'hd'             => 'somewhere.fr',
                    'email'          => self::EMAIL,
                    'email_verified' => true,
                    'at_hash'        => '123456789',
                    'iss'            => 'https://accounts.google.com',
                    'iat'            => 1511522056,
                    'exp'            => 1511525656,
                    'name'           => self::FIRSTNAME . ' ' . self::LASTNAME,
                    'picture'        => self::IMAGEURL,
                    'given_name'     => self::FIRSTNAME,
                    'family_name'    => self::LASTNAME,
                    'locale'         => 'fr',
                ]
            ));

        $provider->setGoogleClient($googleClient);

        return $provider;
    }

    protected function getAvailableFieldsForIdentity()
    {
        return [
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_FIRSTNAME => self::FIRSTNAME,
            Identity::PROPERTY_LASTNAME  => self::LASTNAME,
            Identity::PROPERTY_EMAIL     => self::EMAIL,
            Identity::PROPERTY_IMAGEURL  => self::IMAGEURL,
        ];
    }

    protected function getProviderForFailingIdentity()
    {
        return $this->provideFailingProvider();
    }

    protected function provideFailingProvider()
    {
        $provider = $this->getProvider();

        $guzzle = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $requestGet->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(401));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);

        return $provider;
    }

    protected function authenticateProvider(ProviderInterface $provider)
    {
        $provider->getSession()->set('google-plus.provider.id', '12345678');
    }

    protected function getProvider()
    {
        $googleMock = $this->getMockBuilder('Google_Client')
            ->disableOriginalConstructor()
            ->getMock();

        $googleMock->expects($this->any())
            ->method('createAuthUrl')
            ->will($this->returnValue('https://www.google.com/auth'));

        $google =  new GooglePlus($this->getUrlGeneratorMock(), $this->getMockSession(), $googleMock, $this->getGuzzleMock());

        return $google;
    }

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }
}
