<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

class TokenRepositoryTest extends \PhraseanetTestCase
{
    public function testFindValidToken()
    {
        $repo = self::$DI['app']['repo.tokens'];
        $this->assertSame(self::$DI['token_1'], $repo->findValidToken(self::$DI['token_1']->getValue()));
        $this->assertSame(self::$DI['token_2'], $repo->findValidToken(self::$DI['token_2']->getValue()));
        $this->assertNull($repo->findValidToken(self::$DI['token_invalid']->getValue()));
    }

    public function testFindValidationToken()
    {
        $repo = self::$DI['app']['repo.tokens'];
        $this->assertSame(self::$DI['token_validation'], $repo->findValidationToken(self::$DI['basket_1'], self::$DI['user']));
    }

    public function testExpiredTokens()
    {
        $repo = self::$DI['app']['repo.tokens'];
        $tokens = $repo->findExpiredTokens();
        $this->assertCount(1, $tokens);
        $this->assertSame(self::$DI['token_invalid'], array_pop($tokens));
    }
}
