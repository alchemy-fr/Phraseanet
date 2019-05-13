<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Webhook\WebhookPublisher;

/**
 * @group functional
 * @group legacy
 */
class WebhookEventManipulatorTest extends \PhraseanetTestCase
{
    public function testCreate()
    {
        $manipulator = $this->createManipulator();
        $nbEvents = count(self::$DI['app']['repo.webhook-event']->findAll());
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, [
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ]);
        $this->assertGreaterThan($nbEvents, count(self::$DI['app']['repo.webhook-event']->findAll()));
    }

    public function testDelete()
    {
        $manipulator = $this->createManipulator();
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, [
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ]);
        $countBefore = count(self::$DI['app']['repo.webhook-event']->findAll());
        $manipulator->delete($event);
        $this->assertGreaterThan(count(self::$DI['app']['repo.webhook-event']->findAll()), $countBefore);
    }

    public function testUpdate()
    {
        $manipulator = $this->createManipulator();
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, [
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ]);
        $event->setProcessed(true);
        $manipulator->update($event);
        $event = self::$DI['app']['repo.webhook-event']->find($event->getId());
        $this->assertTrue($event->isProcessed());
    }

    public function testProcessed()
    {
        $manipulator = $this->createManipulator();
        $event = $manipulator->create(WebhookEvent::NEW_FEED_ENTRY, WebhookEvent::FEED_ENTRY_TYPE, [
            'feed_id' => self::$DI['feed_public_entry']->getFeed()->getId(), 'entry_id' => self::$DI['feed_public_entry']->getId()
        ]);
        $manipulator->processed($event);
        $this->assertTrue($event->isProcessed());
    }

    /**
     * @return WebhookEventManipulator
     */
    protected function createManipulator()
    {
        return new WebhookEventManipulator(
            self::$DI['app']['orm.em'],
            self::$DI['app']['repo.webhook-delivery'],
            $this->prophesize(WebhookPublisher::class)->reveal()
        );
    }
}
