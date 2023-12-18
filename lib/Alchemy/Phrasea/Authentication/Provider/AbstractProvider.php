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
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use appbox;
use Doctrine\ORM\EntityManager;
use RandomLib\Generator as RandomGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

abstract class AbstractProvider implements ProviderInterface
{
    protected $generator;
    protected $session;

    /**
     * @var bool
     */
    private $display = true;

    /**
     * @var string
     */
    private $title;

    /**
     * @var UserManipulator
     */
    private  $userManipulator;

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

    /**
     * @var array
     */
    private $options = null;

    private $id;


    protected function __construct(UrlGenerator $generator, SessionInterface $session)
    {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function getId()
    {
        return $this->id ?: $this->getType();
    }

    public function setId($newId)
    {
        $this->id = $newId;
        return $this;
    }

    public function getType()
    {
        $u = explode('\\', static::class);
        return array_pop($u);
    }

    public function getDisplay(): bool
    {
        return $this->display;
    }

    /**
     * @param bool $display
     */
    public function setDisplay(bool $display)
    {
        $this->display = $display;
    }

    /**
     * @deprecated  replaced by getTitle()
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getTitle();
    }

    /**
     * more clear that getName because the key in conf is "title"
     * @return string
     */
    public function getTitle()
    {
        return $this->title ?: $this->getId();
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @param UrlGenerator $generator
     *
     * @return ProviderInterface
     */
    public function setUrlGenerator(UrlGenerator $generator)
    {
        $this->generator = $generator;

        return $this;
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->generator;
    }

    /**
     * @param SessionInterface $session
     *
     * @return ProviderInterface
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates(Identity $identity)
    {
        return [];
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return UserManipulator
     */
    public function getUserManipulator(): UserManipulator
    {
        return $this->userManipulator;
    }

    /**
     * @param UserManipulator $userManipulator
     */
    public function setUserManipulator(UserManipulator $userManipulator)
    {
        $this->userManipulator = $userManipulator;
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * @param UserRepository $userRepository
     */
    public function setUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return ACLProvider
     */
    public function getACLProvider(): ACLProvider
    {
        return $this->ACLProvider;
    }

    /**
     * @param ACLProvider $ACLProvider
     */
    public function setACLProvider(ACLProvider $ACLProvider)
    {
        $this->ACLProvider = $ACLProvider;
    }

    /**
     * @return appbox
     */
    public function getAppbox(): appbox
    {
        return $this->appbox;
    }

    /**
     * @param appbox $appbox
     */
    public function setAppbox(appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    /**
     * @return RandomGenerator
     */
    public function getRandomGenerator(): RandomGenerator
    {
        return $this->randomGenerator;
    }

    /**
     * @param RandomGenerator $randomGenerator
     */
    public function setRandomGenerator(RandomGenerator $randomGenerator)
    {
        $this->randomGenerator = $randomGenerator;
    }

    /**
     * @return UsrAuthProviderRepository
     */
    public function getUsrAuthProviderRepository(): UsrAuthProviderRepository
    {
        return $this->usrAuthProviderRepository;
    }

    /**
     * @param UsrAuthProviderRepository $usrAuthProviderRepository
     */
    public function setUsrAuthProviderRepository(UsrAuthProviderRepository $usrAuthProviderRepository)
    {
        $this->usrAuthProviderRepository = $usrAuthProviderRepository;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function createState()
    {
        return md5(uniqid(microtime(true), true));
    }
}
