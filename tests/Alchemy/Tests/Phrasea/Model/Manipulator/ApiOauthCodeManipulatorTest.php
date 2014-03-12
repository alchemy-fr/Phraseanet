<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;


use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthCodeManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiOauthCodeManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new ApiOauthCodeManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-codes'], self::$DI['app']['random.medium']);
        $nbCodes = count(self::$DI['app']['repo.api-oauth-codes']->findAll());
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $manipulator->create($account, 'http://www.redirect.url');
        $this->assertGreaterThan($nbCodes, count(self::$DI['app']['repo.api-oauth-codes']->findAll()));
    }

    public function testDelete()
    {
        $manipulator = new ApiOauthCodeManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-codes'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $code = $manipulator->create($account, 'http://www.redirect.url');
        $countBefore = count(self::$DI['app']['repo.api-oauth-codes']->findAll());
        $manipulator->delete($code);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-oauth-codes']->findAll()), $countBefore);
    }

    public function testUpdate()
    {

        $manipulator = new ApiOauthCodeManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-codes'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $code = $manipulator->create($account, 'http://www.redirect.url');
        $code->setExpires(new \DateTime());
        $manipulator->update($code);
        $code = self::$DI['app']['repo.api-oauth-codes']->find($code->getCode());
        $this->assertNotNull($code->getExpires());
    }

    /**
     * @setExpectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testSetRedirectUriBadArgumentException()
    {
        $manipulator = new ApiOauthCodeManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.api-oauth-codes'], self::$DI['app']['random.medium']);
        $account = self::$DI['app']['manipulator.api-account']->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $code = $manipulator->create($account, 'http://www.redirect.url');
        try {
            $manipulator->setRedirectUri($code, 'bad-url');
            $this->fail('Invalid argument exception should be raised');
        } catch (InvalidArgumentException $e) {

        }
    }
}
