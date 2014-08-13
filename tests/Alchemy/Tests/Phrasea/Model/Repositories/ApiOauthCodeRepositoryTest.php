<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

class ApiOauthCodeRepositoryTest extends \PhraseanetTestCase
{
    public function testFindByAccount()
    {
        self::$DI['app']['manipulator.api-oauth-code']->create(self::$DI['oauth2-app-acc-user'], 'http://www.callback.fr', time() + 40);
        $codes = self::$DI['app']['EM']->getRepository('Phraseanet:ApiOauthCode')->findByAccount(self::$DI['oauth2-app-acc-user']);
        $this->assertGreaterThan(0, count($codes));
    }
}
