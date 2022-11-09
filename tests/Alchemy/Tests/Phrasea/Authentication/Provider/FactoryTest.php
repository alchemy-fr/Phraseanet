<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Provider\Factory;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use appbox;
use RandomLib\Generator as RandomGenerator;

/**
 * @group functional
 * @group legacy
 */
class FactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideNameAndOptions
     */
    public function testBuild($id, $type, $options, $expected)
    {
        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $userManipulator = $this->getMockBuilder(UserManipulator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ACLProvider = $this->getMockBuilder(ACLProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $appbox = $this->getMockBuilder(appbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        $randomGenerator = $this->getMockBuilder(RandomGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();


        $factory = new Factory($generator, $session, $userManipulator, $userRepository, $ACLProvider, $appbox, $randomGenerator);

        $this->assertInstanceOf($expected, $factory->build($id, $type, true, $id, $options));
    }

    public function provideNameAndOptions()
    {
        return [
            [
                'github-foo',
                'github',
                [
                    'client-id' => 'id',
                    'client-secret' => 'secret'
                ],
                'Alchemy\Phrasea\Authentication\Provider\Github'
            ],
            [
                'linkedin-foo',
                'linkedin',
                [
                    'client-id' => 'id',
                    'client-secret' => 'secret'
                ],
                'Alchemy\Phrasea\Authentication\Provider\Linkedin'
            ],
            [
                'twitter-foo',
                'twitter',
                [
                    'consumer-key' => 'id',
                    'consumer-secret' => 'secret'
                ],
                'Alchemy\Phrasea\Authentication\Provider\Twitter'
            ],
            [
                'viadeo-foo',
                'viadeo',
                [
                    'client-id' => 'id',
                    'client-secret' => 'secret'
                ],
                'Alchemy\Phrasea\Authentication\Provider\Viadeo'
            ],
            [
                'ps-auth-foo',
                'ps-auth',
                [
                    'client-id' => 'id',
                    'client-secret' => 'secret',
                    'base-url' => 'https://api-auth.phrasea.local',
                    'provider-type' => 'oauth',
                    'provider-name' => 'v2',
                    'birthgroup' => 'BIRTHGRP',
                ],
                'Alchemy\Phrasea\Authentication\Provider\PsAuth'
            ]
        ];
    }
}
