<?php

namespace Alchemy\Tests\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\EventProcessorFactory;

/**
 * @group functional
 * @group legacy
 */
class EventProcessorFactoryTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider eventProvider
     */
    public function testGet($type, $expected)
    {
        $factory = new EventProcessorFactory(self::$DI['app']);
        $event = new WebhookEvent();
        $event->setType($type);
        $this->assertInstanceOf($expected, $factory->get($event));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUnknownProcessor()
    {
        $factory = new EventProcessorFactory(self::$DI['app']);
        $event = new WebhookEvent();
        $factory->get($event);
    }

    public function eventProvider()
    {
        return [
            [WebhookEvent::FEED_ENTRY_TYPE, 'Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessor'],
            [WebhookEvent::RECORD_TYPE, 'Alchemy\Phrasea\Webhook\Processor\RecordEventProcessor'],
            [WebhookEvent::RECORD_SUBDEF_TYPE, 'Alchemy\Phrasea\Webhook\Processor\SubdefEventProcessor'],
            [WebhookEvent::ORDER_TYPE, 'Alchemy\Phrasea\Webhook\Processor\OrderNotificationProcessor'],
            [WebhookEvent::USER_TYPE, 'Alchemy\Phrasea\Webhook\Processor\UserProcessor']
        ];
    }
}
