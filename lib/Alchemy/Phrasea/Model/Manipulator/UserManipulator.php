<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Geonames\Connector as GeonamesConnector;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ObjectManager;
use Entities\User;
use Repositories\UserRepository;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Manages common operations for the users.
 */
class UserManipulator implements ManipulatorInterface
{
    /** @var UserManager */
    private $manager;
    /** @var UserRepository */
    private $repository;
    /** @var ObjectManager */
    private $om;

    public function __construct(UserManager $manager, ObjectManager $om)
    {
        $this->manager = $manager;
        $this->om = $om;
        $this->repository = $om->getRepository('Entities\User');
    }
    
    /**
     * @{inheritdoc}
     */
    public function getRepository()
    {
        return $this->repository;
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
     * @throws InvalidArgumentException if login, email or password is not valid.
     * @throws RuntimeException if login or email already exists.
     */
    public function createUser($login, $password, $email = null, $admin = false)
    {
        $user = $this->manager->create();
        $this->setLogin($user, $login);
        $this->setEmail($user, $email);
        $this->setPassword($user, $password);
        $user->setAdmin($admin);
        $this->manager->update($user);

        return $user;
    }

    /**
     * Creates a guest user and returns it.
     *
     * @return User
     *
     * @throws RuntimeException if guest already exists.
     */
    public function createGuest()
    {
        $user = $this->manager->create();
        $this->setLogin($user, User::USER_GUEST);
        $this->setPassword($user, substr(uniqid ('', true), -6));
        $this->manager->update($user);

        return $user;
    }

    /**
     * Creates an auto register user and returns it.
     *
     * @return User
     *
     * @throws RuntimeException if autoregister already exists.
     */
    public function createAutoRegister()
    {
        $user = $this->manager->create();
        $this->setLogin($user, User::USER_AUTOREGISTER);
        $this->setPassword($user, substr(uniqid ('', true), -6));
        $this->manager->update($user);

        return $user;
    }

    /**
     * Creates a template user and returns it.
     *
     * @param string $name
     * @param User $template
     *
     * @return User
     *
     * @throws InvalidArgumentException if name is not valid.
     * @throws RuntimeException if name already exists.
     */
    public function createTemplate($name, User $template)
    {
        $user = $this->manager->create();
        $this->setLogin($user, $name);
        $this->setPassword($user, substr(uniqid ('', true), -6));
        $this->setModelOf($user, $template);
        $this->manager->update($user);

        return $user;
    }

    /**
     * Sets the password for the given user.
     *
     * @param user $user
     * @param string $password
     *
     * @throws InvalidArgumentException if password is not valid.
     */
    public function setPassword(User $user, $password)
    {
        if (trim($password) === '') {
            throw new InvalidArgumentException('Invalid password.');
        }

        $this->manager->onUpdatePassword($user, $password);
    }

    /**
     * Sets the template for the given user.
     *
     * @param User $user
     * @param User $template
     *
     * @throws InvalidArgumentException if user and template are the same.
     */
    public function setModelOf(User $user, User $template)
    {
        if ($user->getLogin() === $template->getLogin()) {
            throw new InvalidArgumentException(sprintf('Can not set same user %s as template.', $user->getLogin()));
        }

        $this->manager->onUpdateModel($user, $template);
    }

    /**
     * Sets the geonameid for the given user.
     *
     * @param User $user
     * @param integer $geonameid
     *
     * @throws InvalidArgumentException if geonameid is not valid.
     */
    public function setGeonameId(User $user, $geonameid)
    {
        $user->setGeonameId($geonameid);
        $this->manager->onUpdateGeonameId($user);
    }

    /**
     * Sets the login for the given user.
     *
     * @param User $user
     * @param sring $login
     *
     * @throws InvalidArgumentException if login is not valid.
     * @throws RuntimeException if login already exists.
     */
    public function setLogin(User $user, $login)
    {
        if (trim($login) === '') {
            throw new InvalidArgumentException('Invalid login.');
        }

        if (null !== $this->repository->findByLogin($login)) {
            throw new RuntimeException(sprintf('User with login %s already exists.', $login));
        }

        $user->setLogin($login);
    }

    /**
     * Sets email for given user.
     *
     * @param User $user
     * @param string $email
     *
     * @throws InvalidArgumentException if email is not valid or already exists.
     * @throws RuntimeException if email already exists.
     */
    public function setEmail(User $user, $email)
    {
        if (null !== $email && !preg_match('/.+@.+\..+/', trim($email))) {
            throw new InvalidArgumentException('Invalid email.');
        }

        if (null !== $this->repository->findByEmail($email)) {
            throw new RuntimeException(sprintf('User with email %s already exists.', $email));
        }

        $user->setEmail($email);
    }

    /**
     * Promotes the given users.
     *
     * @param User|array $user
     */
    public function promote($users)
    {
        if (!is_array($users)) {
            $users = array($users);
        }

        foreach ($users as $user) {
            $this->doPromoteUser($user);
        }
    }

    /**
     * Demotes the given users.
     *
     * @param User|array $users
     */
    public function demote($users)
    {
        if (!is_array($users)) {
            $users = array($users);
        }
        
        foreach ($users as $user) {
            $this->doDemoteUser($user);
        }
    }
    
    /**
     * Promove given user.
     * 
     * @param User $user
     */
    private function doDemoteUser(User $user)
    {
        $user->setAdmin(false);
        $this->manager->update($user);
    }
    
    /**
     * Demotes given user.
     * 
     * @param User $user
     */
    private function doPromoteUser(User $user)
    {
        $user->setAdmin(true);
        $this->manager->update($user);
    }
}
