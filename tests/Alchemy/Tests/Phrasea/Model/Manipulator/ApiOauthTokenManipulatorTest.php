<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;

class ApiOauthTokenManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new ApiOauthTokenManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-tokens'], self::$DI['app']['random.medium']);
        $nbTokens = count(self::$DI['app']['repo.api-oauth-tokens']->findAll());
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator->create($account);
        $this->assertGreaterThan($nbTokens, count(self::$DI['app']['repo.api-oauth-tokens']->findAll()));
    }

    public function testDelete()
    {
        $manipulator = new ApiOauthTokenManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-tokens'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $token = $manipulator->create($account);
        $countBefore = count(self::$DI['app']['repo.api-oauth-tokens']->findAll());
        $manipulator->delete($token);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-oauth-tokens']->findAll()), $countBefore);
    }

    public function testUpdate()
    {

        $manipulator = new ApiOauthTokenManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-tokens'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $token = $manipulator->create($account);
        $token->setSessionId(123456);
        $manipulator->update($token);
        $token = self::$DI['app']['repo.api-oauth-tokens']->find($token->getOauthToken());
        $this->assertEquals(123456, $token->getSessionId());
    }

    public function testRenew()
    {
        $manipulator = new ApiOauthTokenManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-tokens'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $token = $manipulator->create($account);
        $oauthTokenBefore = $token->getOauthToken();
        $manipulator->renew($token);
        $this->assertNotEquals($oauthTokenBefore, $token->getOauthToken());
    }
}
