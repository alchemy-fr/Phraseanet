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

use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Persistence\ObjectManager;

class ApiAccountManipulator implements ManipulatorInterface
{
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * @param ApiApplication $application
     * @param User           $user
     * @param string         $version
     * @return ApiAccount
     */
    public function create(ApiApplication $application, User $user, $version)
    {
        $account = new ApiAccount();
        $account->setUser($user);
        $account->setApplication($application);
        $account->setApiVersion($version);

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
