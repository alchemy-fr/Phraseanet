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
use Alchemy\Phrasea\Model\Entities\UserNotificationSetting;
use Alchemy\Phrasea\Model\Entities\UserQuery;
use Alchemy\Phrasea\Model\Entities\UserSetting;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Manages common operations for the users.
 */
class UserManipulator implements ManipulatorInterface
{
    /** @var PasswordEncoderInterface */
    protected $passwordEncoder;
    /** @var UserManager */
    private $manager;
    /** @var GeonamesConnector */
    private $geonamesConnector;

    public function __construct(UserManager $manager, PasswordEncoderInterface $passwordEncoder, GeonamesConnector $connector)
    {
        $this->manager = $manager;
        $this->passwordEncoder = $passwordEncoder;
        $this->geonamesConnector = $connector;
    }

    /**
     * @{inheritdoc}
     */
    public function getRepository()
    {
        return $this->manager->getObjectManager()->getRepository('Phraseanet:User');
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

        return $user;
    }

    /**
     * Deletes a user.
     *
     * @param User|User[] $users
     */
    public function delete($users)
    {
        foreach ($this->makeTraversable($users) as $user) {
            $user->setDeleted(true);
            $user->setEmail(null);

            $this->manager->delete($user);
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
        $user->setModelOf($owner);
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
            $country = $this->geonamesConnector->geoname($geonameid)->get('country');

            $user->setGeonameId($geonameid);

            if (isset($country['code'])) {
                $user->setCountry($country['code']);
            }
        } catch (GeonamesExceptionInterface $e) {
            $user->setCountry(null);
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
        $userQuery->setUser($user);

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
        $user->setNonce(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
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
        if (null !== $this->getRepository()->findByLogin($login)) {
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

        if (null !== $this->getRepository()->findByEmail($email)) {
            throw new RuntimeException(sprintf('User with email %s already exists.', $email));
        }

        $user->setEmail($email);
    }

    /**
     * Makes given variable traversable.
     *
     * @param mixed $var
     *
     * @return array
     */
    private function makeTraversable($var)
    {
        if (!is_array($var) && !$var instanceof \Traversable) {
            return [$var];
        }

        return $var;
    }
}
