<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

class WebhookEventDeliveryRepositoryTest extends \PhraseanetTestCase
{
    public function testFindUndeliveredEvents()
    {
        $events = self::$DI['app']['EM']->getRepository('Phraseanet:WebhookEventDelivery')->findUndeliveredEvents();
        $this->assertCount(1, $events);
    }
}
