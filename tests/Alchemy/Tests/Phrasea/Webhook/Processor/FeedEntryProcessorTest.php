<?php

namespace Alchemy\Tests\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\Processor\FeedEntryProcessor;

/**
 * @group functional
 * @group legacy
 */
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
        $processor = new FeedEntryProcessor(
            self::$DI['app'],
            self::$DI['app']['repo.feed-entries'],
            self::$DI['app']['phraseanet.user-query']
        );
        $this->assertEquals($processor->process($event), null);
    }

    public function testProcessWithMissingDataProperty()
    {
        $event = new WebhookEvent();
        $event->setData([
            'feed_id' => 0,
        ]);
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $processor = new FeedEntryProcessor(
            self::$DI['app'],
            self::$DI['app']['repo.feed-entries'],
            self::$DI['app']['phraseanet.user-query']
        );
        $this->assertEquals($processor->process($event), null);
    }

    public function testProcess()
    {
        $event = new WebhookEvent();
        $event->setData([
            'feed_id'   => self::$DI['feed_public_entry']->getFeed()->getId(),
            'entry_id'  => self::$DI['feed_public_entry']->getId(),
            'url'       => 'server_name',
            'instance_name' => 'instance_name',
            'event_time'    => new \DateTime()
        ]);
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $processor = new FeedEntryProcessor(
            self::$DI['app'],
            self::$DI['app']['repo.feed-entries'],
            self::$DI['app']['phraseanet.user-query']
        );
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $processor->process($event));
    }
}
