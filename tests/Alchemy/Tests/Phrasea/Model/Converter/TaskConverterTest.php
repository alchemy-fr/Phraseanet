<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TaskConverter;

class TaskConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $task = self::$DI['app']['orm.em']->find('Phraseanet:Task', 1);

        $converter = new TaskConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Task'));
        $this->assertSame($task, $converter->convert(1));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Task prout not found.
     */
    public function testConvertFailure()
    {
        $converter = new TaskConverter(self::$DI['app']['orm.em']->getRepository('Phraseanet:Task'));
        $converter->convert('prout');
    }
}
