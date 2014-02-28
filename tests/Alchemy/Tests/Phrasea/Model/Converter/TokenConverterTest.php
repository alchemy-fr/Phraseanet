<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TokenConverter;

class TokenConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $token = self::$DI['token_1'];

        $converter = new TokenConverter(self::$DI['app']['repo.tokens']);
        $this->assertSame($token, $converter->convert($token->getValue()));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Token is not valid.
     */
    public function testConvertFailure()
    {
        $converter = new TokenConverter(self::$DI['app']['repo.tokens']);
        $converter->convert('prout');
    }
}
