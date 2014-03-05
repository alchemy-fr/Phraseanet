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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiOauthRefreshtoken;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use RandomLib\Generator;

class ApiOauthRefreshTokenManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;
    private $randomGenerator;

    public function __construct(ObjectManager $om, EntityRepository $repo, Generator $random)
    {
        $this->om = $om;
        $this->repository = $repo;
        $this->randomGenerator = $random;
    }

    public function create(ApiAccount $account, \DateTime $expire, $scope = null)
    {
        $refreshToken = new ApiOauthRefreshtoken();

        $refreshToken->setCode($this->getNewToken());
        $refreshToken->setAccount($account);
        $refreshToken->setExpires($expire);
        $refreshToken->setScope($scope);

        $this->update($refreshToken);

        return $refreshToken;
    }

    public function delete(ApiOauthRefreshtoken $refreshToken)
    {
        $this->om->remove($refreshToken);
        $this->om->flush();
    }

    public function update(ApiOauthRefreshtoken $refreshToken)
    {
        $this->om->persist($refreshToken);
        $this->om->flush();
    }

    private function getNewToken()
    {
        do {
            $refreshToken = $this->randomGenerator->generateString(32, \random::LETTERS_AND_NUMBERS);
        } while (null !== $this->repository->find($refreshToken));

        return $refreshToken;
    }
}
