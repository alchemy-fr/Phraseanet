<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use appbox;
use Doctrine\ORM\EntityManager;
use RandomLib\Generator as RandomGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;


class Factory
{
    private $generator;
    private $session;
    /**
     * @var UserManipulator
     */
    private $userManipulator;
    /**
     * @var UserRepository
     */
    private $userRepository;
    /**
     * @var ACLProvider
     */
    private $ACLProvider;
    /**
     * @var appbox
     */
    private $appbox;
    /**
     * @var RandomGenerator
     */
    private $randomGenerator;
    /**
     * @var UsrAuthProviderRepository
     */
    private $usrAuthProviderRepository;
    /**
     * @var EntityManager
     */
    private $entityManager;


    public function __construct(UrlGenerator $generator, SessionInterface $session,
                                UserManipulator $userManipulator, UserRepository $userRepository,
                                ACLProvider $ACLProvider, appbox $appbox, RandomGenerator $randomGenerator,
                                UsrAuthProviderRepository $usrAuthProviderRepository,
                                EntityManager $entityManager
    )
    {
        $this->generator = $generator;
        $this->session = $session;
        $this->userManipulator = $userManipulator;
        $this->userRepository = $userRepository;
        $this->ACLProvider = $ACLProvider;
        $this->appbox = $appbox;
        $this->randomGenerator = $randomGenerator;
        $this->usrAuthProviderRepository = $usrAuthProviderRepository;
        $this->entityManager = $entityManager;
    }

    public function build(string $id, string $type, bool $display, string $title, array $options = [])
    {
        $type = implode('', array_map(function ($chunk) {
            return ucfirst(strtolower($chunk));
        }, explode('-', $type)));

        $class_name = sprintf('%s\\%s', __NAMESPACE__, $type);

        if (!class_exists($class_name)) {
            throw new InvalidArgumentException(sprintf('Invalid provider %s', $type));
        }

        /** @var AbstractProvider $o */
        $o = $class_name::create($this->generator, $this->session, $options);   // v1 bc compat : can't change

        $o->setId($id);
        $o->setDisplay($display);
        $o->setTitle($title);
        $o->setOptions($options);

        $o->setUserManipulator($this->userManipulator);
        $o->setUserRepository($this->userRepository);
        $o->setACLProvider($this->ACLProvider);
        $o->setAppbox($this->appbox);
        $o->setRandomGenerator($this->randomGenerator);
        $o->setUsrAuthProviderRepository($this->usrAuthProviderRepository);
        $o->setEntityManager($this->entityManager);

        return $o;
    }
}
