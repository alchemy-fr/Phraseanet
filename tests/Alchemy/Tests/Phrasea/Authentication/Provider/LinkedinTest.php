<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Linkedin;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

class LinkedinTest extends ProviderTestCase
{
    public function provideDataForFailingCallback()
    {
        $state = md5(mt_rand());

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, array('state' => $state));

        $provider1 = $this->getProvider();
        $provider1->setGuzzleClient($this->getGuzzleMock(401));
        $provider1->getSession()->set('linkedin.provider.state', $state);

        $mock = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $requestPost = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();
        $queryString->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());

        $requestGet->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects($this->at(0))
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $response->expects($this->at(1))
            ->method('getStatusCode')
            ->will($this->returnValue(401));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $requestPost->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $mock->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $mock->expects($this->any())
            ->method('post')
            ->will($this->returnValue($requestPost));


        $provider2 = $this->getProvider();
        $provider2->setGuzzleClient($mock);
        $provider2->getSession()->set('linkedin.provider.state', $state);


        return array(
            array($this->getProvider(), $this->getRequestMock()),
            array($provider1, $request),
            array($provider2, $request),
        );
    }

    public function getProviderForLogout()
    {
        return $this->getProvider();
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

        $queryString->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());

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
        $provider->getSession()->set('linkedin.provider.state', $state);

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, array('state' => $state));

        return array(
            array($provider, $request),
        );
    }

    public function getTestOptions()
    {
        return array(
            'client-id'     => 'linkedin-client-id',
            'client-secret' => 'linkedin-client-secret',
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

        $queryString->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());

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
                'positions' => array(
                    '_total' => 1,
                    'values' => array(
                        array(
                            'company' => array(
                                'name' => self::COMPANY
                            )
                        )
                    )
                ),
                'emailAddress' => self::EMAIL,
                'firstName'    => self::FIRSTNAME,
                'id'           => self::ID,
                'pictureUrl'   => self::IMAGEURL,
                'lastName'     => self::LASTNAME,
            ))));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);
        $provider->getSession()->set('linkedin.provider.id', 'linkedin-id');

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
            Identity::PROPERTY_COMPANY   => self::COMPANY,
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

        $queryString->expects($this->any())
            ->method('add')
            ->will($this->returnSelf());

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

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }

    protected function authenticate(ProviderInterface $provider)
    {
        $provider->getSession()->set('linkedin.provider.id', 'linkedin-id');
    }

    protected function getProvider()
    {
        return new Linkedin($this->getUrlGeneratorMock(), $this->getMockSession(), $this->getGuzzleMock(), 'key', 'secret');
    }
}
