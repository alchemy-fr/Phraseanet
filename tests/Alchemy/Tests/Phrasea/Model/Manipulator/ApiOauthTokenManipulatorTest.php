<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiOauthTokenRepository;

/**
 * @group functional
 * @group legacy
 */
class ApiOauthTokenManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $app = $this->getApplication();
        $tokenRepository = $this->getTokenRepository($app);

        $manipulator = new ApiOauthTokenManipulator($app['orm.em'], $tokenRepository, $app['random.medium']);
        $nbTokens = count($tokenRepository->findAll());
        $account = $this->createAccount($app);
        $manipulator->create($account);
        $this->assertGreaterThan($nbTokens, count($tokenRepository->findAll()));
    }

    public function testDelete()
    {
        $app = $this->getApplication();
        $tokenRepository = $this->getTokenRepository($app);

        $manipulator = new ApiOauthTokenManipulator($app['orm.em'], $tokenRepository, $app['random.medium']);
        $account = $this->createAccount($app);
        $token = $manipulator->create($account);
        $countBefore = count($tokenRepository->findAll());
        $manipulator->delete($token);
        $this->assertGreaterThan(count($tokenRepository->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $app = $this->getApplication();
        $tokenRepository = $this->getTokenRepository($app);

        $manipulator = new ApiOauthTokenManipulator($app['orm.em'], $tokenRepository, $app['random.medium']);
        $account = $this->createAccount($app);
        $token = $manipulator->create($account);
        $token->setSessionId(123456);
        $manipulator->update($token);
        $token = $tokenRepository->find($token->getOauthToken());
        $this->assertEquals(123456, $token->getSessionId());
    }

    public function testRenew()
    {
        $app = $this->getApplication();
        $tokenRepository = $this->getTokenRepository($app);

        $manipulator = new ApiOauthTokenManipulator($app['orm.em'], $tokenRepository, $app['random.medium']);
        $account = $this->createAccount($app);
        $token = $manipulator->create($account);
        $oauthTokenBefore = $token->getOauthToken();
        $manipulator->renew($token);
        $this->assertNotEquals($oauthTokenBefore, $token->getOauthToken());
    }

    /**
     * @param Application $app
     * @return ApiAccountManipulator
     */
    private function getApiAccountManipulator(Application $app)
    {
        return $app['manipulator.api-account'];
    }

    /**
     * @param Application $app
     * @return ApiOauthTokenRepository
     */
    private function getTokenRepository(Application $app)
    {
        $tokenRepository = $app['repo.api-oauth-tokens'];
        return $tokenRepository;
    }

    /**
     * @param Application $app
     * @return ApiAccount
     */
    private function createAccount(Application $app)
    {
        return $this->getApiAccountManipulator($app)
            ->create(self::$DI['oauth2-app-user'], self::$DI['user'], V2::VERSION);
    }
}
