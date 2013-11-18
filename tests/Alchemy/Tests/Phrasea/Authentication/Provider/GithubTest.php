<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Github;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

class GithubTest extends ProviderTestCase
{
    public function provideDataForFailingCallback()
    {
        $state = md5(mt_rand());

        $provider = $this->provideFailingProvider();
        $provider->getSession()->set('github.provider.state', $state . mt_rand());

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        return [
            [$this->getProvider(), $this->getRequestMock()],
            [$provider, $request],
        ];
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
        $provider->getSession()->set('github.provider.state', $state);

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        return [
            [$provider, $request],
        ];
    }

    public function getTestOptions()
    {
        return [
            'client-id'     => 'github-client-id',
            'client-secret' => 'github-client-secret',
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
                'id'         => self::ID,
                'name'       => self::FIRSTNAME . ' ' . self::LASTNAME,
                'email'      => self::EMAIL,
                'avatar_url' => self::IMAGEURL,
            ])));

        $requestGet->expects($this->any())
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle->expects($this->any())
            ->method('get')
            ->will($this->returnValue($requestGet));

        $provider->setGuzzleClient($guzzle);
        $provider->getSession()->set('github.provider.id', 'github-id');

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
            Identity::PROPERTY_EMAIL     => self::EMAIL,
            Identity::PROPERTY_IMAGEURL  => self::IMAGEURL,
        ];
    }

    protected function authenticate(ProviderInterface $provider)
    {
        $provider->getSession()->set('github.provider.id', 'github-id');
    }

    protected function getProvider()
    {
        return new Github($this->getUrlGeneratorMock(), $this->getMockSession(), $this->getGuzzleMock(), 'key', 'secret');
    }
}
