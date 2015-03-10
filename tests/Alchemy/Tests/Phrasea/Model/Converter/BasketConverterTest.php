<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\BasketConverter;

class BasketConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);

        $converter = new BasketConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket'));
        $this->assertSame($basket, $converter->convert($basket->getId()));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Basket prout not found.
     */
    public function testConvertFailure()
    {
        $converter = new BasketConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Basket'));
        $converter->convert('prout');
    }
}
