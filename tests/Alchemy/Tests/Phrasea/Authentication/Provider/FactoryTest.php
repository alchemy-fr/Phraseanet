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

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FactoryTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideNameAndOptions')]
    public function testBuild($id, $type, $options, $expected)
    {
        $generator = $this->createStub('Symfony\Component\Routing\Generator\UrlGenerator');

        $session = $this->createStub('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $userManipulator = $this->createStub(UserManipulator::class);
        $userRepository = $this->createStub(UserRepository::class);
        $ACLProvider = $this->createStub(ACLProvider::class);
        $appbox = $this->createStub(appbox::class);
        $randomGenerator = $this->createStub(RandomGenerator::class);
        $usrAuthProviderRepository = $this->createStub(UsrAuthProviderRepository::class);
        $entityManager = $this->createStub(EntityManager::class);

        $factory = new Factory($generator, $session, $userManipulator, $userRepository, $ACLProvider, $appbox, $randomGenerator,
            $usrAuthProviderRepository,
            $entityManager
        );

        $this->assertInstanceOf($expected, $factory->build($id, $type, true, $id, $options));
    }

    public static function provideNameAndOptions()
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
