<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthCodeManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiOauthCodeRepository;

/**
 * @group functional
 * @group legacy
 */
class ApiOauthCodeManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $app = $this->getApplication();
        $oauthCodesRepository = $this->getOAuthCodesRepository($app);

        $manipulator = new ApiOauthCodeManipulator($app['orm.em'], $oauthCodesRepository, $app['random.medium']);
        $nbCodes = count($oauthCodesRepository->findAll());
        $account = $this->getApiAccount($app);
        $manipulator->create($account, 'http://www.redirect.url', time() + 30);
        $this->assertGreaterThan($nbCodes, count($oauthCodesRepository->findAll()));
    }

    public function testDelete()
    {
        $app = $this->getApplication();
        $oauthCodesRepository = $this->getOAuthCodesRepository($app);

        $manipulator = new ApiOauthCodeManipulator($app['orm.em'], $oauthCodesRepository, $app['random.medium']);
        $account = $this->getApiAccount($app);
        $code = $manipulator->create($account, 'http://www.redirect.url', time() + 30);
        $countBefore = count($oauthCodesRepository->findAll());
        $manipulator->delete($code);
        $this->assertGreaterThan(count($oauthCodesRepository->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $app = $this->getApplication();
        $oauthCodesRepository = $this->getOAuthCodesRepository($app);

        $manipulator = new ApiOauthCodeManipulator($app['orm.em'], $oauthCodesRepository, $app['random.medium']);
        $account = $this->getApiAccount($app);
        $code = $manipulator->create($account, 'http://www.redirect.url', $t = time() + 30);
        $code->setExpires(time() + 40);
        $manipulator->update($code);
        $code = $oauthCodesRepository->find($code->getCode());
        $this->assertGreaterThan($t, $code->getExpires());
    }

    /**
     * @setExpectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testSetRedirectUriBadArgumentException()
    {
        $app = $this->getApplication();
        $oauthCodesRepository = $this->getOAuthCodesRepository($app);

        $manipulator = new ApiOauthCodeManipulator($app['orm.em'], $oauthCodesRepository, $app['random.medium']);
        $account = $this->getApiAccount($app);
        $code = $manipulator->create($account, 'http://www.redirect.url', time() + 30);
        try {
            $manipulator->setRedirectUri($code, 'bad-url');
            $this->fail('Invalid argument exception should be raised');
        } catch (InvalidArgumentException $e) {

        }
    }

    /**
     * @param Application $app
     * @return ApiOauthCodeRepository
     */
    private function getOAuthCodesRepository(Application $app)
    {
        return $app['repo.api-oauth-codes'];
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
     * @return ApiAccount
     */
    private function getApiAccount(Application $app)
    {
        return $this->getApiAccountManipulator($app)
            ->create(self::$DI['oauth2-app-user'], self::$DI['user'], V2::VERSION);
    }
}
