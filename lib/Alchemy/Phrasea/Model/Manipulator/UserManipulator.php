<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Geonames\Connector as GeonamesConnector;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Alchemy\Phrasea\Core\Event\User\CreatedEvent;
use Alchemy\Phrasea\Model\Entities\UserNotificationSetting;
use Alchemy\Phrasea\Model\Entities\UserQuery;
use Alchemy\Phrasea\Model\Entities\UserSetting;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use RandomLib\Generator;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Alchemy\Phrasea\Core\Event\User\UserEvents;
use Alchemy\Phrasea\Core\Event\User\DeletedEvent;


/**
 * Manages common operations for the users.
 */
class UserManipulator implements ManipulatorInterface
{
    /**
     * @var PasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var UserManager
     */
    private $manager;

    /**
     * @var GeonamesConnector
     */
    private $geonamesConnector;

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param UserManager $manager
     * @param PasswordEncoderInterface $passwordEncoder
     * @param GeonamesConnector $connector
     * @param EntityRepository $repo
     * @param Generator $generator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        UserManager $manager,
        PasswordEncoderInterface $passwordEncoder,
        GeonamesConnector $connector,
        EntityRepository $repo,
        Generator $generator,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->manager = $manager;
        $this->generator = $generator;
        $this->passwordEncoder = $passwordEncoder;
        $this->geonamesConnector = $connector;
        $this->repository = $repo;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Creates a user and returns it.
     *
     * @param string  $login
     * @param string  $password
     * @param string  $email
     * @param Boolean $admin
     *
     * @return User
     *
     * @throws InvalidArgumentException if login or email is not valid.
     * @throws RuntimeException         if login or email already exists.
     */
    public function createUser($login, $password, $email = null, $admin = false)
    {
        $user = $this->manager->create();
        $this->doSetLogin($user, $login);
        $this->doSetEmail($user, $email);
        $this->doSetPassword($user, $password);
        $user->setAdmin($admin);
        $this->manager->update($user);

        $this->dispatcher->dispatch(
            UserEvents::CREATED,
            new CreatedEvent(
                $user
            )
        );

        return $user;
    }

    /**
     * Deletes a user.
     *
     * @param User|User[] $users
     * @param array $grantedBaseIdList  List of the old granted base_id per userId  [user_id => [base_id, ...]  ]
     */
    public function delete($users, array $grantedBaseIdList = array())
    {
        /** @var User $user */
        foreach ($this->makeTraversable($users) as $user) {
            $old_id = $user->getId();
            $old_login = $user->getLogin();
            $old_email = $user->getEmail();

            $user->setLogin(sprintf('(#deleted_%s_%d)', $old_login, $old_id));
            $user->setDeleted(true);
            $user->setEmail(null);

            $this->manager->delete($user);

            $this->dispatcher->dispatch(
                UserEvents::DELETED,
                new DeletedEvent(
                    null,
                    array(
                        'user_id'           => $old_id,
                        'login'             => $old_login,
                        'email'             => $old_email,
                        'grantedBaseIds'    => isset($grantedBaseIdList[$old_id]) ? $grantedBaseIdList[$old_id] : []
                    )
                )
            );
        }
    }

    /**
     * Creates a template user and returns it.
     *
     * @param string $login
     * @param User   $owner
     *
     * @return User The template
     *
     * @throws InvalidArgumentException if login is not valid.
     * @throws RuntimeException         if login already exists.
     */
    public function createTemplate($login, User $owner)
    {
        $user = $this->manager->create();
        $this->doSetLogin($user, $login);
        $user->setTemplateOwner($owner);
        $this->manager->update($user);

        return $user;
    }

    /**
     * Sets the password for a user.
     *
     * @param user   $user
     * @param string $password
     */
    public function setPassword(User $user, $password)
    {
        $this->doSetPassword($user, $password);
        $this->manager->update($user);
    }

    /**
     * Sets the geonameid for a user.
     *
     * @param User    $user
     * @param integer $geonameid
     *
     * @throws InvalidArgumentException if geonameid is not valid.
     */
    public function setGeonameId(User $user, $geonameid)
    {
        if (null === $geonameid) {
            return;
        }

        try {
            $data = $this->geonamesConnector->geoname($geonameid);
            $country = $data->get('country');

            $user->setGeonameId($geonameid);
            $user->setCity($data->get('name'));

            if (isset($country['code'])) {
                $user->setCountry($country['code']);
            }
        } catch (GeonamesExceptionInterface $e) {
            $user->setCountry('');
            $user->setCity('');
        }

        $this->manager->update($user);
    }

    /**
     * Sets email for a user.
     *
     * @param User   $user
     * @param string $email
     *
     * @throws InvalidArgumentException if email is not valid or already exists.
     * @throws RuntimeException         if email already exists.
     */
    public function setEmail(User $user, $email)
    {
        $this->doSetEmail($user, $email);
        $this->manager->update($user);
    }

    /**
     * Promotes users.
     *
     * @param User|User[] $users
     */
    public function promote($users)
    {
        foreach ($this->makeTraversable($users) as $user) {
            $user->setAdmin(true);
            $this->manager->update($user);
        }
    }

    /**
     * Demotes users.
     *
     * @param User|User[] $users
     */
    public function demote($users)
    {
        foreach ($this->makeTraversable($users) as $user) {
            $user->setAdmin(false);
            $this->manager->update($user);
        }
    }

    /**
     * Sets a preference setting for a user.
     *
     * @param User   $user
     * @param string $name
     * @param string $value
     */
    public function setUserSetting(User $user, $name, $value)
    {
        if ($user->getSettings()->containsKey($name)) {
            $user->getSettings()->get($name)->setValue($value);
        } else {
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
            $userSetting->setName($name);
            $userSetting->setValue($value);
            $user->addSetting($userSetting);
        }

        $this->manager->update($user);
    }

    /**
     * Sets a notification setting for a user.
     *
     * @param User   $user
     * @param string $name
     * @param string $value
     */
    public function setNotificationSetting(User $user, $name, $value)
    {
        if ($user->getNotificationSettings()->containsKey($name)) {
            $user->getNotificationSettings()->get($name)->setValue((Boolean) $value);
        } else {
            $userSetting = new UserNotificationSetting();
            $userSetting->setUser($user);
            $userSetting->setName($name);
            $userSetting->setValue($value);
            $user->addNotificationSettings($userSetting);
        }

        $this->manager->update($user);
    }

    /**
     * Logs a user query.
     *
     * @param User   $user
     * @param string $query
     */
    public function logQuery(User $user, $query)
    {
        $userQuery = new UserQuery();
        $userQuery->setUser($user);
        $userQuery->setQuery($query);

        $user->addQuery($userQuery);

        $this->manager->update($user);
    }

    /**
     * Sets the password for a user.
     *
     * @param user   $user
     * @param string $password
     */
    private function doSetPassword(User $user, $password)
    {
        $user->setNonce($this->generator->generateString(64));
        $user->setPassword($this->passwordEncoder->encodePassword($password, $user->getNonce()));
        $user->setSaltedPassword(true);
    }

    /**
     * Sets the login for a user.
     *
     * @param User  $user
     * @param sring $login
     *
     * @throws InvalidArgumentException if login is not valid.
     * @throws RuntimeException         if login already exists.
     */
    private function doSetLogin(User $user, $login)
    {
        if (null !== $this->repository->findByLogin($login)) {
            throw new RuntimeException(sprintf('User with login %s already exists.', $login));
        }

        $user->setLogin($login);
    }

    /**
     * Sets email for a user.
     *
     * @param User   $user
     * @param string $email
     *
     * @throws InvalidArgumentException if email is not valid or already exists.
     * @throws RuntimeException         if email already exists.
     */
    private function doSetEmail(User $user, $email)
    {
        if (null !== $email && false === (Boolean) \Swift_Validate::email($email)) {
            throw new InvalidArgumentException(sprintf('Email %s is not legal.', $email));
        }

        if (($email !== null) && (null !== $this->repository->findByEmail($email))) {
            throw new RuntimeException(sprintf('User with email %s already exists.', $email));
        }

        $user->setEmail($email);
    }

    /**
     * Makes given variable traversable.
     *
     * @param User|User[] $var
     *
     * @return array|\Traversable|User[]
     */
    private function makeTraversable($var)
    {
        if (!is_array($var) && !$var instanceof \Traversable) {
            return [$var];
        }

        return $var;
    }

    public function updateUser(User $user)
    {
        $this->manager->update($user);
    }
}
