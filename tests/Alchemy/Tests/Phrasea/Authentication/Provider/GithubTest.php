<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Github;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
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

        $guzzle = $this->createMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->createMock('Guzzle\Http\Message\RequestInterface');
        $requestPost = $this->createMock('Guzzle\Http\Message\EntityEnclosingRequestInterface');

        $queryString = $this->createMock('Guzzle\Http\QueryString');

        $requestGet
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->createMock('Guzzle\Http\Message\Response');

        $response
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $requestGet
            ->method('send')
            ->will($this->returnValue($response));

        $requestPost
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle
            ->method('get')
            ->will($this->returnValue($requestGet));

        $guzzle
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

        $guzzle = $this->createMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->createMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->createMock('Guzzle\Http\QueryString');

        $requestGet
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->createMock('Guzzle\Http\Message\Response');

        $response
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $response
            ->method('getBody')
            ->with($this->equalTo(true))
            ->will($this->returnValue(json_encode([
                'id'         => self::ID,
                'name'       => self::FIRSTNAME . ' ' . self::LASTNAME,
                'email'      => self::EMAIL,
                'avatar_url' => self::IMAGEURL,
            ])));

        $requestGet
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle
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

        $guzzle = $this->createMock('Guzzle\Http\ClientInterface');

        $requestGet = $this->createMock('Guzzle\Http\Message\RequestInterface');

        $queryString = $this->createMock('Guzzle\Http\QueryString');

        $requestGet
            ->method('getQuery')
            ->will($this->returnValue($queryString));

        $response = $this->createMock('Guzzle\Http\Message\Response');

        $response
            ->method('getStatusCode')
            ->will($this->returnValue(401));

        $requestGet
            ->method('send')
            ->will($this->returnValue($response));

        $guzzle
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

    protected function authenticateProvider(ProviderInterface $provider)
    {
        $provider->getSession()->set('github.provider.id', 'github-id');
    }

    protected function getProvider()
    {
        return new Github($this->getUrlGeneratorMock(), $this->getMockSession(), ['client-id'=>'id', 'client-secret'=>'secret'], $this->getGuzzleMock());
    }
}
