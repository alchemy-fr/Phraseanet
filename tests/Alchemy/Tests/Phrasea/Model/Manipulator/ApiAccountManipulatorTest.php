<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Controller\Api\V1;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;

class ApiAccountManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-accounts']);
        $nbApps = count(self::$DI['app']['repo.api-accounts']->findAll());
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-accounts']->findAll()));
        $this->assertFalse($account->isRevoked());
        $this->assertEquals(V1::VERSION, $account->getApiVersion());
        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-accounts']->findAll()));
    }

    public function testDelete()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $accountMem = clone $account;
        $countBefore = count(self::$DI['app']['repo.api-accounts']->findAll());
        self::$DI['app']['manipulator.api-oauth-token']->create($account);
        $manipulator->delete($account);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-accounts']->findAll()), $countBefore);
        $tokens = self::$DI['app']['repo.api-oauth-tokens']->findOauthTokens($accountMem);
        $this->assertEquals(0, count($tokens));
    }

    public function testUpdate()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $account->setApiVersion(24);
        $manipulator->update($account);
        $account = self::$DI['app']['repo.api-accounts']->find($account->getId());
        $this->assertEquals(24, $account->getApiVersion());
    }

    public function testAuthorizeAccess()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator->authorizeAccess($account);
        $this->assertFalse($account->isRevoked());
    }

    public function testRevokeAccess()
    {
        $manipulator = new ApiAccountManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-accounts']);
        $account = $manipulator->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator->revokeAccess($account);
        $this->assertTrue($account->isRevoked());
    }
}
