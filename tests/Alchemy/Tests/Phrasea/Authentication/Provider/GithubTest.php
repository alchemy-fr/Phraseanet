<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Github;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use GuzzleHttp\Psr7\Response;

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

        $guzzle = $this->getGuzzleMockWithResponses([
            new Response(200, [], json_encode(['access_token' => 'github-access-token'])),
            new Response(200, [], json_encode(['id' => self::ID])),
        ]);

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

        $guzzle = $this->getGuzzleMock(200, json_encode([
            'id'         => self::ID,
            'name'       => self::FIRSTNAME . ' ' . self::LASTNAME,
            'email'      => self::EMAIL,
            'avatar_url' => self::IMAGEURL,
        ]));

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

        $guzzle = $this->getGuzzleMock(401);

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
