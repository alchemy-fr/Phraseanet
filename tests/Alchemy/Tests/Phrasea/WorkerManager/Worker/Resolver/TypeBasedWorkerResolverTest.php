<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker\Resolver;

use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Alchemy\Phrasea\WorkerManager\Worker\Factory\CallableWorkerFactory;
use Alchemy\Phrasea\WorkerManager\Worker\Factory\WorkerFactoryInterface;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\TypeBasedWorkerResolver;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInterface;

class TypeBasedWorkerResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testClassImplements()
    {
        $sut = new TypeBasedWorkerResolver();

        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\Resolver\\WorkerResolverInterface', $sut);
    }

    public function testGetFactories()
    {
        $workerFactory = $this->getMockBuilder(WorkerFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $sut = new TypeBasedWorkerResolver();

        $sut->addFactory(MessagePublisher::SUBDEF_CREATION_TYPE, $workerFactory);

        $this->assertContainsOnlyInstancesOf(WorkerFactoryInterface::class, $sut->getFactories());
    }

    public function testGetWorkerSuccess()
    {
        $worker = $this->getMockBuilder(WorkerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workerFactory = $this->getMockBuilder(CallableWorkerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workerFactory->method('createWorker')->will($this->returnValue($worker));

        $sut = new TypeBasedWorkerResolver();

        $sut->addFactory(MessagePublisher::SUBDEF_CREATION_TYPE, $workerFactory);


        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface',
            $sut->getWorker(MessagePublisher::SUBDEF_CREATION_TYPE, ['mock-message']));

    }

    public function testGetWorkerWrongTypeThrowException()
    {
        $worker = $this->getMockBuilder(WorkerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workerFactory = $this->getMockBuilder(CallableWorkerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workerFactory->method('createWorker')->will($this->returnValue($worker));

        $sut = new TypeBasedWorkerResolver();

        $sut->addFactory(MessagePublisher::SUBDEF_CREATION_TYPE, $workerFactory);

        $this->setExpectedException(\RuntimeException::class);

        $sut->getWorker(MessagePublisher::WRITE_METADATAS_TYPE, ['mock-message']);

    }
}
