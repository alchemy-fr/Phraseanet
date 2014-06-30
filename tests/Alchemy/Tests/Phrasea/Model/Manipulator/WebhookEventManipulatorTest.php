<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;

class WebhookEventManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = new WebhookEventManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-delivery']);
        $nbEvents = count(self::$DI['app']['repo.webhook-event']->findAll());
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, array(
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ));
        $this->assertGreaterThan($nbEvents, count(self::$DI['app']['repo.webhook-event']->findAll()));
    }

    public function testDelete()
    {
        $manipulator = new WebhookEventManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-event']);
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, array(
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ));
        $countBefore = count(self::$DI['app']['repo.webhook-event']->findAll());
        $manipulator->delete($event);
        $this->assertGreaterThan(count(self::$DI['app']['repo.webhook-event']->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $manipulator = new WebhookEventManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-event']);
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, array(
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ));
        $event->setProcessed(true);
        $manipulator->update($event);
        $event = self::$DI['app']['repo.webhook-event']->find($event->getId());
        $this->assertTrue($event->isProcessed());
    }

    public function testProcessed()
    {
        $manipulator = new WebhookEventManipulator(self::$DI['app']['EM'], self::$DI['app']['repo.webhook-event']);
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, array(
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ));
        $manipulator->processed($event);
        $this->assertTrue($event->isProcessed());
    }
}
