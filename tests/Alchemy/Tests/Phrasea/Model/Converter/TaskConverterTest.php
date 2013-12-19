<?php

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TaskConverter;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $task = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Task', 1);

        $converter = new TaskConverter(self::$DI['app']['EM']);
        $this->assertSame($task, $converter->convert(1));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Task prout not found.
     */
    public function testConvertFailure()
    {
        $converter = new TaskConverter(self::$DI['app']['EM']);
        $converter->convert('prout');
    }
}
