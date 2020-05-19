<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\WorkerManager\Subscriber\ExportSubscriber;
use Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber;

/**
 * @covers Alchemy\Phrasea\WorkerManager\Subscriber\ExportSubscriber
 *  @covers Alchemy\Phrasea\WorkerManager\Subscriber\RecordSubscriber
 */
class SubscriberTest extends \PhraseanetTestCase
{
    public function testCallsImplements()
    {
        $app = new Application(Application::ENV_TEST);
        $app['alchemy_worker.message.publisher'] = $this->prophesize('Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher');

        $sexportSubscriber = new ExportSubscriber($app['alchemy_worker.message.publisher']->reveal());
        $this->assertInstanceOf('Symfony\\Component\\EventDispatcher\\EventSubscriberInterface', $sexportSubscriber);
    }

    public function testIfPublisheMessageOnSubscribeEvent()
    {
        $app = new Application(Application::ENV_TEST);
        $app['alchemy_worker.message.publisher'] = $this->getMockBuilder('Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher')
            ->disableOriginalConstructor()
            ->getMock();

        $app['alchemy_worker.type_based_worker_resolver'] = $this->getMockBuilder('Alchemy\Phrasea\WorkerManager\Worker\Resolver\TypeBasedWorkerResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $app['alchemy_worker.message.publisher']->expects($this->atLeastOnce())->method('publishMessage');

        $event = $this->prophesize('Alchemy\Phrasea\Core\Event\ExportMailEvent');
        $sut = new ExportSubscriber($app['alchemy_worker.message.publisher']);
        $sut->onExportMailCreate($event->reveal());

    }
}
