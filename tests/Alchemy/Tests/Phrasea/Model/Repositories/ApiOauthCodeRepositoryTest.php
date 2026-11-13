<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ApiOauthCodeRepositoryTest extends \PhraseanetTestCase
{
    public function testFindByAccount()
    {
        self::$DI['app']['manipulator.api-oauth-code']->create(self::$DI['oauth2-app-acc-user'], 'http://www.callback.fr', time() + 40);
        $codes = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthCode')->findByAccount(self::$DI['oauth2-app-acc-user']);
        $this->assertGreaterThan(0, count($codes));
    }
}
