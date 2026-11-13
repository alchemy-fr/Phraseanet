<?php

namespace Alchemy\Tests\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TaskConverter;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class TaskConverterTest extends \PhraseanetTestCase
{
    public function testConvert()
    {
        $task = self::$DI['app']['orm.em']->find('Alchemy\Phrasea\Model\Entities\Task', 1);

        $converter = new TaskConverter(self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Task'));
        $this->assertSame($task, $converter->convert(1));
    }

    public function testConvertFailure()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Task prout not found.');
        $converter = new TaskConverter(self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\Task'));
        $converter->convert('prout');
    }
}
