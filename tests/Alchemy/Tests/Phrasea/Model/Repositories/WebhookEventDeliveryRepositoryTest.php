<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

/**
 * @group functional
 * @group legacy
 */
class WebhookEventDeliveryRepositoryTest extends \PhraseanetTestCase
{
    public function testFindUndeliveredEvents()
    {
        $events = self::$DI['app']['orm.em']->getRepository('Phraseanet:WebhookEventDelivery')->findUndeliveredEvents();
        $this->assertCount(1, $events);
    }
}
