<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TokenConverter;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TokenConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $token = self::$DI['token_1'];

        $converter = new TokenConverter(self::$DI['app']['repo.tokens']);
        $this->assertSame($token, $converter->convert($token->getValue()));
    }

    public function testConvertFailure()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Token is not valid.');
        $converter = new TokenConverter(self::$DI['app']['repo.tokens']);
        $converter->convert('prout');
    }
}
