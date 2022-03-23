<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\ProcessPool;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use PhpAmqpLib\Channel\AMQPChannel;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

class WorkerInvokerTest extends \PhraseanetTestCase
{
    public function testClassImplements()
    {
        $processPool = $this->prophesize(ProcessPool::class);

        $sut = new WorkerInvoker($processPool->reveal());

        $this->assertInstanceOf('Psr\\Log\\LoggerAwareInterface', $sut);
    }

    public function testInvokeWorkerSuccess()
    {
        $process = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $process ->expects($this->exactly(1))
            ->method('start')
        ;

        $processPool = $this->getMockBuilder(ProcessPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processPool->method('getWorkerProcess')->will($this->returnValue($process));

        $channel = $this->prophesize(AMQPChannel::class);

        $sut = new WorkerInvoker($processPool);

        $sut->invokeWorker(MessagePublisher::SUBDEF_CREATION_TYPE, json_encode(['mock-payload']), $channel);
    }

    public function testInvokeWorkerWhenThrowException()
    {
        $process = $this->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $process ->expects($this->exactly(1))
            ->method('start')
            ->will($this->throwException(new ProcessRuntimeException()))
        ;

        $processPool = $this->getMockBuilder(ProcessPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processPool->method('getWorkerProcess')->will($this->returnValue($process));

        $channel = $this->prophesize(AMQPChannel::class);

        $sut = new WorkerInvoker($processPool);

        try {
            $sut->invokeWorker(MessagePublisher::SUBDEF_CREATION_TYPE, json_encode(['mock-payload']), $channel);
            $this->fail('Should have raised an exception');
        } catch (\Exception $e) {

        }
    }
}
