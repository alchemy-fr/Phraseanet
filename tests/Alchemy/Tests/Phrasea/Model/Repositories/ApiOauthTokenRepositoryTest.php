<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ApiOauthTokenRepositoryTest extends \PhraseanetTestCase
{
    public function testFindDeveloperToken()
    {
        $tok = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthToken')->findByAccount(self::$DI['oauth2-app-acc-user']);
        $this->assertNotNull($tok);
    }

    public function testFindOauthTokens()
    {
        $tokens = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\ApiOauthToken')->findOauthTokens(self::$DI['oauth2-app-acc-user']);
        $this->assertGreaterThan(0,  count($tokens));
    }
}
