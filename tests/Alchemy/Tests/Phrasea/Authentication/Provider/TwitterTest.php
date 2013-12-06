<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Provider\Twitter;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

class TwitterTest extends ProviderTestCase
{
    public function testGetSetGuzzleClient()
    {
        $this->markTestSkipped('testGetSetGuzzleClient disabled for facebook');
    }

    public function testGetSetTwitterClient()
    {
        $provider = $this->getProvider();
        $this->assertInstanceOf('tmhOAuth', $provider->getTwitterClient());
        $client = $this->getMockBuilder('tmhOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->setTwitterClient($client);
        $this->assertEquals($client, $provider->getTwitterClient());
    }

    public function getProviderForLogout()
    {
        return $this->getProvider();
    }

    /**
     * @expectedException \Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException
     */
    public function testAuthenticateWithFailure()
    {
        $provider = $this->getProvider();

        $provider->getTwitterClient()->expects($this->once())
            ->method('request')
            ->will($this->returnValue(401));

        $provider->authenticate();
    }

    public function getProviderForAuthentication()
    {
        $provider = $this->getProvider();

        $provider->getTwitterClient()->expects($this->once())
            ->method('request')
            ->will($this->returncallback(function () use ($provider) {
                $provider->getTwitterClient()->response = [
                    'response' => [
                        'oauth_token' => 'twitter-oauth-token',
                    ]
                ];

                return 200;
            }));

        return $provider;
    }

    public function provideDataForFailingCallback()
    {
        $request = $this->getRequestMock();
        $this->addQueryParameter($request, []);

        $provider1 = $this->getProvider();
        $provider1->getTwitterClient()->expects($this->once())
            ->method('request')
            ->will($this->returnValue(401));

        $first = true;
        $provider2 = $this->getProvider();
        $provider2->getTwitterClient()->expects($this->exactly(2))
            ->method('request')
            ->will($this->returnCallback(function () use (&$first, $provider2) {
                if (!$first) {
                    return 401;
                } else {

                    $provider2->getTwitterClient()->response = [
                        'response' => [
                            'oauth_token' => 'twitter-oauth-token',
                        ]
                    ];

                    $first = false;

                    return 200;
                }
            }));

        return [
            [$provider1, $request],
            [$provider2, $request],
        ];
    }

    public function provideDataForSuccessCallback()
    {
        $provider = $this->getProvider();

        $state = md5(mt_rand());
        $provider->getSession()->set('twitter.provider.state', $state);

        $request = $this->getRequestMock();
        $this->addQueryParameter($request, ['state' => $state]);

        $provider->getTwitterClient()->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(function ($method) use ($provider) {
                switch ($method) {
                    case 'POST':
                        $provider->getTwitterClient()->response = [
                            'response' => [
                                'oauth_token' => 'twitter-oauth-token',
                            ]
                        ];
                        break;
                    case 'GET':
                        $provider->getTwitterClient()->response = [
                            'response' => json_encode([
                                'id' => self::ID,
                            ])
                        ];
                        break;
                    default:
                        throw new \InvalidArgumentException(sprintf('Invalid method %s', $method));
                        break;
                }

                return 200;
            }));

        $provider->getTwitterClient()->expects($this->once())
            ->method('extract_params')
            ->will($this->returnValue([
               'oauth_token_secret' => 'token secret',
               'oauth_token' => 'token',
            ]));

        return [
            [$provider, $request],
        ];
    }

    public function getTestOptions()
    {
        return [
            'consumer-key' => 'twitter-consumer-key',
            'consumer-secret' => 'twitter-consumer-secret',
        ];
    }

    protected function getProviderForSuccessIdentity()
    {
        $provider = $this->getProvider();
        $provider->getSession()->set('twitter.provider.access_token', [
            'oauth_token' => 'twitter token',
            'oauth_token_secret' => 'token secret',
        ]);

        $provider->getTwitterClient()->expects($this->once())
            ->method('request')
            ->will($this->returncallback(function () use ($provider) {
                $provider->getTwitterClient()->response = [
                    'response' => json_encode([
                        'screen_name' => self::USERNAME,
                        'profile_image_url_https' => self::IMAGEURL,
                        'id' => self::ID,
                    ])
                ];

                return 200;
            }));

        return $provider;
    }

    protected function getAvailableFieldsForIdentity()
    {
        return [
            Identity::PROPERTY_ID        => self::ID,
            Identity::PROPERTY_USERNAME  => self::USERNAME,
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

        return $provider;
    }

    protected function authenticateProvider(ProviderInterface $provider)
    {
        $provider->getSession()->set('twitter.provider.id', '12345');
    }

    protected function getProvider()
    {
        $twitter = $this->getMockBuilder('tmhOAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $twitter->config = $this->getTestOptions();

        return new Twitter($this->getUrlGeneratorMock(), $this->getMockSession(), $twitter);
    }
}
