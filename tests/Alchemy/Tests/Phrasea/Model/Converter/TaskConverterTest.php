<?php

namespace Alchemy\Phrasea\Model\Converter;

use Alchemy\Phrasea\Model\Converter\TaskConverter;
use Alchemy\Phrasea\Model\Entities\Task;

class TaskConverterTest extends \PhraseanetPHPUnitAbstract
{
    public function testConvert()
    {
        $task = new Task();
        $task
            ->setName('task 1')
            ->setJobId('Alchemy\Phrasea\TaskManager\Job\NullJob');

        self::$DI['app']['EM']->persist($task);
        self::$DI['app']['EM']->flush();

        $converter = new TaskConverter(self::$DI['app']['EM']);
        $this->assertSame($task, $converter->convert($task->getId()));
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Task prout not found.
     */
    public function testConvertFailure()
    {
        $converter = new TaskConverter(self::$DI['app']['EM']);
        $converter->convert('prout');
    }
}
