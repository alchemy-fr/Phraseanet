<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\GooglePlus;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

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
        $this->addQueryParameter($request, array('state' => $state));

        return array(
            array($this->provideFailingProvider(), $this->getRequestMock()),
            array($provider, $request),
        );
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
        $this->addQueryParameter($request, array(
            'state' => $state,
        ));

        $ticket = $this->getMockBuilder('Google_LoginTicket')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->getGoogleClient()->expects($this->once())
            ->method('verifyIdToken')
            ->will($this->returnValue($ticket));

        return array(
            array($provider, $request),
        );
    }

    public function getTestOptions()
    {
        return array(
            'client-id'     => 'google-plus-client-id',
            'client-secret' => 'google-plus-client-secret',
        );
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
            ->will($this->returnValue(json_encode(array(
                'email' => self::EMAIL
            ))));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);
        $provider->getSession()->set('google-plus.provider.id', '12345678');

        $people = $this->getMockBuilder('Google_PeopleServiceResource')
            ->disableOriginalConstructor()
            ->getMock();

        $people->expects($this->once())
            ->method('get')
            ->will($this->returnValue(array(
                'name' => array(
                    'givenName' => self::FIRSTNAME,
                    'familyName' => self::LASTNAME,
                ),
                'id' => self::ID,
                'image' => array(
                    'url' => self::IMAGEURL
                )
        )));

        $provider->getGooglePlusService()->people = $people;

        return $provider;
    }

    protected function getAvailableFieldsForIdentity()
    {
        return array(
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_FIRSTNAME => self::FIRSTNAME,
            Identity::PROPERTY_LASTNAME  => self::LASTNAME,
            Identity::PROPERTY_EMAIL     => self::EMAIL,
            Identity::PROPERTY_IMAGEURL  => self::IMAGEURL,
        );
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

    protected function authenticate(ProviderInterface $provider)
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

        $plus = $this->getMockBuilder('Google_PlusService')
            ->disableOriginalConstructor()
            ->getMock();


        $google =  new GooglePlus($this->getUrlGeneratorMock(), $this->getMockSession(), $googleMock, $this->getGuzzleMock());
        $google->setGooglePlusService($plus);

        return $google;
    }

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }
}
