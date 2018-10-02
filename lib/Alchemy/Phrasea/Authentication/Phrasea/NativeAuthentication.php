<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\Request;

class NativeAuthentication implements PasswordAuthenticationInterface
{
    /** @var UserManipulator */
    private $userManipulator;
    /** @var PasswordEncoder */
    private $encoder;
    /** @var OldPasswordEncoder */
    private $oldEncoder;
    /** @var UserRepository */
    private $repository;

    public function __construct(PasswordEncoder $encoder, OldPasswordEncoder $oldEncoder, UserManipulator $userManipulator, UserRepository $repo)
    {
        $this->userManipulator = $userManipulator;
        $this->encoder = $encoder;
        $this->oldEncoder = $oldEncoder;
        $this->repository = $repo;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsrId($username, $password, Request $request)
    {
        if (null === $user = $this->repository->findRealUserByLogin($username)) {
            return null;
        }

        if ($user->isSpecial()) {
            return null;
        }

        // check locked account
        if ($user->isMailLocked()) {
            throw new AccountLockedException('The account is locked', $user->getId());
        }

        if (false === $user->isSaltedPassword()) {
            // we need a quick update and continue
            if ($this->oldEncoder->isPasswordValid($user->getPassword(), $password, $user->getNonce())) {
                $this->userManipulator->setPassword($user, $password);
            }
        }

        if (false === $this->encoder->isPasswordValid($user->getPassword(), $password, $user->getNonce())) {
            return null;
        }

        return $user->getId();
    }
}
