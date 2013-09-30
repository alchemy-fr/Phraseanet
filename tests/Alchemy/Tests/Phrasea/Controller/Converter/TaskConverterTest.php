<?php

namespace Alchemy\Phrasea\Controller\Converter;

use Alchemy\Phrasea\Controller\Converter\TaskConverter;
use Entities\Task;

class TaskConverterTest extends \PhraseanetPHPUnitAbstract
{
    public function testConvert()
    {
        $task = new Task();
        $task
            ->setName('task 1')
            ->setClassname('Alchemy\Phrasea\TaskManager\Job\NullJob');

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
