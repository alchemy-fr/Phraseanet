<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Viadeo;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

class ViadeoTest extends ProviderTestCase
{
    public function provideDataForFailingCallback()
    {
        $state = md5(mt_rand());

        $provider = $this->provideFailingProvider();
        $provider->getSession()->set('viadeo.provider.state', $state . mt_rand());

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        return [
            [$this->getProvider(), $this->getRequestMock()],
            [$provider, $request],
        ];
    }

    public function getProviderForLogout()
    {
        $provider = $this->getProvider();

        $guzzle = $this->getMock('Guzzle\Http\ClientInterface');
        $requestGet = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $queryString->expects($this->exactly(3))
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
            ->will($this->returnValue(302));

        $requestGet->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);

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
        $provider->getSession()->set('viadeo.provider.state', $state);

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        return [
            [$provider, $request],
        ];
    }

    public function getTestOptions()
    {
        return [
            'client-id'     => 'viadeo-client-id',
            'client-secret' => 'viadeo-client-secret',
        ];
    }

    protected function getProviderForAuthentication()
    {
        return $this->getProvider();
    }

    protected function getProviderForSuccessIdentity()
    {
        $provider = $this->getProvider();

        $guzzle = $this->getMock('Guzzle\Http\ClientInterface');

        $requestGet1 = $this->getMock('Guzzle\Http\Message\RequestInterface');
        $requestGet2 = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->getMockBuilder('Guzzle\Http\QueryString')
            ->disableOriginalConstructor()
            ->getMock();

        $requestGet1->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));
        $requestGet2->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response1 = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $response2 = $this->getMockBuilder('Guzzle\Http\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $response1->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));
        $response2->expects($this->any())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $response1->expects($this->any())
            ->method('getBody')
            ->with($this->equalTo(true))
            ->will($this->returnValue(json_encode([
                'id'            => self::ID,
                'first_name'    => self::FIRSTNAME,
                'last_name'     => self::LASTNAME,
                'picture_large' => self::IMAGEURL,
                'nickname'      => self::USERNAME,
            ])));

        $response2->expects($this->any())
            ->method('getBody')
            ->with($this->equalTo(true))
            ->will($this->returnValue(json_encode([
                'data' => [
                   [
                       'company_name' => self::COMPANY
                   ]
                ],
            ])));

        $requestGet1->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response1));
        $requestGet2->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response2));

        $guzzle->expects($this->at(0))
            ->method('get')
            ->will($this->returnValue($requestGet1));

        $guzzle->expects($this->at(1))
            ->method('get')
            ->will($this->returnValue($requestGet2));

        $provider->setGuzzleClient($guzzle);
        $provider->getSession()->set('viadeo.provider.id', 'viadeo-id');

        return $provider;
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

    protected function getAvailableFieldsForIdentity()
    {
        return [
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_FIRSTNAME => self::FIRSTNAME,
            Identity::PROPERTY_LASTNAME  => self::LASTNAME,
            Identity::PROPERTY_USERNAME  => self::USERNAME,
            Identity::PROPERTY_IMAGEURL  => self::IMAGEURL,
            Identity::PROPERTY_COMPANY   => self::COMPANY,
        ];
    }

    protected function authenticateProvider(ProviderInterface $provider)
    {
        $provider->getSession()->set('viadeo.provider.id', 'viadeo-id');
    }

    protected function getProvider()
    {
        return new Viadeo($this->getUrlGeneratorMock(), $this->getMockSession(), $this->getGuzzleMock(), 'key', 'secret');
    }
}
