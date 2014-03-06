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
use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class ApiAccountManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;

    public function __construct(ObjectManager $om, EntityRepository $repo)
    {
        $this->om = $om;
        $this->repository = $repo;
    }

    public function create(ApiApplication $application, User $user = null)
    {
        $account = new ApiAccount();
        $account->setUser($user);
        $account->setApplication($application);
        $account->setApiVersion(V1::VERSION);

        $this->update($account);

        return $account;
    }

    public function delete(ApiAccount $account)
    {
        $this->om->remove($account);
        $this->om->flush();
    }

    public function update(ApiAccount $account)
    {
        $this->om->persist($account);
        $this->om->flush();
    }

    public function authorizeAccess(ApiAccount $account)
    {
        $account->setRevoked(false);

        $this->update($account);
    }

    public function revokeAccess(ApiAccount $account)
    {
        $account->setRevoked(true);

        $this->update($account);
    }
}
