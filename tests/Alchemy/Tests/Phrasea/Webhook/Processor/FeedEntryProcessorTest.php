<?php

namespace Alchemy\Tests\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessor;

class FeedEntryProcessorTest extends \PhraseanetTestCase
{

    public function testProcessWithNoFeedId()
    {
        $event = new WebhookEvent();
        $event->setData([
            'feed_id' => 0,
            'entry_id' => 0
        ]);
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $processor = new FeedEntryProcessor($event, self::$DI['app']);
        $this->assertEquals($processor->process(), null);
    }

    public function testProcessWithMissingDataProperty()
    {
        $event = new WebhookEvent();
        $event->setData([
            'feed_id' => 0,
        ]);
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $processor = new FeedEntryProcessor($event, self::$DI['app']);
        $this->assertEquals($processor->process(), null);
    }

    public function testProcess()
    {
        $event = new WebhookEvent();
        $event->setData([
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(),
            'entry_id' => self::$DI['feed_public_entry']->getId()
        ]);
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $processor = new FeedEntryProcessor($event, self::$DI['app']);
        $this->assertEquals($processor->process(), null);
    }
}
