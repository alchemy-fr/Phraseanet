<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\BasketConverter;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BasketConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);

        $converter = new BasketConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket'));
        $this->assertSame($basket, $converter->convert($basket->getId()));
    }

    public function testConvertFailure()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Basket prout not found.');
        $converter = new BasketConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket'));
        $converter->convert('prout');
    }
}
