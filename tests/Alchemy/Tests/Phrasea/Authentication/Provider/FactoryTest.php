<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Provider\Factory;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use appbox;
use Doctrine\ORM\EntityManager;
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
        $usrAuthProviderRepository = $this->getMockBuilder(UsrAuthProviderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new Factory($generator, $session, $userManipulator, $userRepository, $ACLProvider, $appbox, $randomGenerator,
            $usrAuthProviderRepository,
            $entityManager
        );

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
                'openid-foo',
                'openid',
                [
                    'client-id' => 'id',
                    'client-secret' => 'secret',
                    'base-url' => 'https://api-auth.phrasea.local',
                    'realm-name' => 'phrasea',
                ],
                'Alchemy\Phrasea\Authentication\Provider\Openid'
            ]
        ];
    }
}
