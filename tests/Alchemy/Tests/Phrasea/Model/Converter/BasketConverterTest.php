<?php

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\BasketConverter;
use Alchemy\Phrasea\Model\Entities\Task;

class BasketConverterTest extends \PhraseanetPHPUnitAbstract
{
    public function testConvert()
    {
        $basket = $this->insertOneBasket();

        $converter = new BasketConverter(self::$DI['app']['EM']);
        $this->assertSame($basket, $converter->convert($basket->getId()));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Basket prout not found.
     */
    public function testConvertFailure()
    {
        $converter = new BasketConverter(self::$DI['app']['EM']);
        $converter->convert('prout');
    }
}
