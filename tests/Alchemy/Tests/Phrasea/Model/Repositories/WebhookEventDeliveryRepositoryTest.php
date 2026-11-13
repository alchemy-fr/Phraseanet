<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class WebhookEventDeliveryRepositoryTest extends \PhraseanetTestCase
{
    public function testFindUndeliveredEvents()
    {
        $events = self::$DI['app']['orm.em']->getRepository('Alchemy\Phrasea\Model\Entities\WebhookEventDelivery')->findUndeliveredEvents();
        $this->assertCount(1, $events);
    }
}
